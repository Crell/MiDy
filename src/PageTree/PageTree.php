<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\PageTree\Model\File;
use Crell\MiDy\PageTree\Model\Folder;
use Crell\MiDy\PageTree\Model\PageRecord;
use Crell\MiDy\PageTree\Parser\Parser;

class PageTree
{
    /**
     * @var array<string, string>
     *     A map from logical folder paths to the physical paths they correspond to.
     */
    private array $mountPoints = [];

    public function __construct(
        private readonly PageRepo $cache,
        private readonly Parser $parser,
        string $rootPhysicalPath,
    ) {
        $this->mount($rootPhysicalPath, '/');
    }

    public function mount(string $physicalPath, string $logicalPath): void
    {
        $this->mountPoints[$logicalPath] = $physicalPath;
    }

    /**
     * Returns the Folder read-object for this path.
     */
    public function folder(string $logicalPath): ?Folder
    {
        $data = $this->loadFolder($logicalPath);
        return $data ? new Folder($data, $this) : null;
    }

    /**
     * Loads a single page by path.
     */
    public function page(string $path): ?Page
    {
        // We don't need the folder, but this ensures
        // the folder has been parsed so that the files
        // table is populated.
        $this->folder(dirname($path));

        $page = $this->cache->readPage($path);

        if (!$page) {
            return null;
        }

        $needsReindex = array_any($page->files, fn (File $file): bool => $file->mtime < filemtime($file->physicalPath));
        if ($needsReindex) {
            $this->reindexFolder($page->folder);
            $page = $this->cache->readPage($path);
        }

        return $page;
    }

    public function queryPages(
        ?string $folder = null,
        bool $deep = false,
        bool $includeHidden = false,
        bool $routableOnly = true,
        array $anyTag = [],
        ?\DateTimeInterface $publishedBefore = new \DateTimeImmutable(),
        array $orderBy = [],
        int $pageSize = PageRepo::DefaultPageSize,
        int $pageNum = 1
    ): Pagination {
        $result = $this->cache->queryPages(
            folder: $folder,
            deep: $deep,
            includeHidden: $includeHidden,
            routableOnly: $routableOnly,
            anyTag: $anyTag,
            publishedBefore: $publishedBefore,
            orderBy: $orderBy,
            limit: $pageSize,
            offset: $pageSize * ($pageNum - 1)
        );

        $numPages = (int)ceil($result->total / $pageSize);

        $items = new BasicPageSet($this->upcastPages($result->pages));

        return new Pagination(
            total: $result->total,
            pageSize: $pageSize,
            pageCount: $numPages,
            pageNum: $pageNum,
            items: $items,
        );
    }

    /**
     * Retrieves all visible pages under the specified path.
     *
     * @todo This should probably return lazily for better scalability.
     *
     * @return iterable<string, Page>
     */
    public function folderAllPages(string $folderPath, int $pageSize = PHP_INT_MAX, int $pageNum = 1): iterable
    {
        return $this->queryPages(folder: $folderPath, pageSize: $pageSize, pageNum: $pageNum)->items;
    }

    public function folderAnyTag(string $folderPath, array $tags, int $pageSize = PageRepo::DefaultPageSize, int $pageNum = 1): Pagination
    {
        return $this->queryPages(folder: $folderPath, anyTag: $tags, pageSize: $pageSize, pageNum: $pageNum);
    }

    public function anyTag(array $tags, int $pageSize = 10, int $pageNum = 1): Pagination
    {
        return $this->queryPages(anyTag: $tags, pageSize: $pageSize, pageNum: $pageNum);
    }

    public function reindexAll(string $logicalRoot = '/'): void
    {
        $this->reindexFolder($logicalRoot);

        foreach ($this->cache->childFolders($logicalRoot) as $child) {
            $this->reindexAll($child->logicalPath);
        }
    }

    private function loadFolder(string $logicalPath): ?ParsedFolder
    {
        $folder = $this->cache->readFolder($logicalPath);

        if (!$folder || $folder->mtime < filemtime($folder->physicalPath)) {
            $folder = $this->reindexFolder($logicalPath);
        }

        return $folder;
    }

    /**
     * Re-parse a folder at a given location by reparsing its parent's contents.
     */
    private function reindexFolder(string $logicalPath): ?ParsedFolder
    {
        // If it's one of the mount roots, just parse that directly.
        if (array_key_exists($logicalPath, $this->mountPoints)) {
            $ret = $this->parser->parseFolder($this->mountPoints[$logicalPath], $logicalPath, $this->mountPoints);
            // In case of parser error, fail here.
            if (!$ret) {
                return null;
            }
            // In case there is another mount point that is an immediate child,
            // reindex that too so we get any index file in it.
            foreach ($this->mountPoints as $logicalMount => $physicalMount) {
                if ($logicalMount !== $logicalPath && dirname($logicalMount) === $logicalPath) {
                    $this->reindexFolder($logicalMount);
                }
            }
            return $this->cache->readFolder($logicalPath);
        }

        // Otherwise, we need to get the logical parent folder and get its physical
        // path, so we know what to parse.  If the parent is not yet indexed,
        // it will get reindexed, too.
        $parts = explode('/', $logicalPath);
        $slug = array_pop($parts);
        $parentFolderPath = '/' . implode('/', array_filter($parts));
        $parent = $this->loadFolder($parentFolderPath);
        if (!$parent) {
            return null;
        }
        $ret = $this->parser->parseFolder($parent->physicalPath . '/' . $slug, $logicalPath, $this->mountPoints);
        // In case of parser error, fail here.
        if (!$ret) {
            return null;
        }
        return $this->cache->readFolder($logicalPath);
    }

    /**
     * Converts index pages into folders with an index page.
     *
     * I'm not sure this is the right place
     * for it, but it works for now.
     *
     * @param array<PageRecord> $pages
     */
    private function upcastPages(array $pages): array
    {
        $ret = [];
        foreach ($pages as $page) {
            if ($page->isFolder) {
                $ret[] = new Folder($this->loadFolder($page->logicalPath), $this, $page);
            } else {
                $ret[] = $page;
            }
        }

        return $ret;
    }
}
