<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\PageTreeDB2\Parser\Parser;

class PageTree
{
    /**
     * @var array<string, string>
     *     A map from logical folder paths to the physical paths they correspond to.
     */
    private array $mountPoints = [];

    public function __construct(
        private readonly PageCacheDB $cache,
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
     * Retrieves all visible pages under the specified path.
     *
     * @todo This should probably return lazily for better scalability.
     *
     * @return array<string, Page>
     */
    public function pages(string $folderPath, int $limit = PHP_INT_MAX, int $offset = 0): array
    {
        $files = $this->cache->readFilesForFolder($folderPath, $limit, $offset);
        return $this->instantiatePages($files);
    }

    public function pagesAnyTag(string $folderPath, array $tags, int $pageSize = 10, int $pageNum = 1): array
    {
        $files = $this->cache->readPagesInFolderAnyTag($folderPath, $tags);
        return $this->instantiatePages($files);
    }

    public function pagesAllTags(string $folderPath, array $tags, int $pageSize = 10, int $pageNum = 1): Pagination
    {
        $total = $this->cache->countPagesInFolder($folderPath);
        $data = $this->cache->readPagesInFolderAllTags($folderPath, $tags, $pageSize, $pageSize * ($pageNum - 1));

        return $this->paginate($pageSize, $pageNum, $total, $data);
    }

    public function anyTag(array $tags, int $pageSize = 10, int $pageNum = 1): Pagination
    {
        $total = $this->cache->countPages();
        $data = $this->cache->readPagesAnyTag($tags, $pageSize, $pageSize * ($pageNum - 1));

        return $this->paginate($pageSize, $pageNum, $total, $data);
    }

    public function paginateFolder(string $folderPath, int $pageSize, int $pageNum = 1): Pagination
    {
        $total = $this->cache->countPagesInFolder($folderPath);
        $data = $this->cache->readFilesForFolder($folderPath, $pageSize, $pageSize * ($pageNum - 1));

        return $this->paginate($pageSize, $pageNum, $total, $data);
    }

    /**
     * @param iterable<ParsedFile> $data
     */
    private function paginate(int $pageSize, int $pageNum, int $total, iterable $data): Pagination
    {
        $numPages = (int)ceil($total / $pageSize);

        $items = new BasicPageSet($this->instantiatePages($data));

        return new Pagination(
            total: $total,
            pageSize: $pageSize,
            pageCount: $numPages,
            pageNum: $pageNum,
            items: $items,
        );
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

        $files = $this->cache->readPage($path);

        return $this->makePage($path, $files);
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
     * @param array<ParsedFile> $files
     * @return array<Page>
     */
    private function instantiatePages(array $files): array
    {
        $grouped = [];
        foreach ($files as $file) {
            if (filemtime($file->physicalPath) > $file->mtime) {
                // Need to rescan this file.
                $file = $this->parser->parseFile(new \SplFileInfo($file->physicalPath), $file->folder);
                $this->cache->writeFile($file);
            }
            $grouped[$file->logicalPath][] = $file;
        }

        $pages = [];
        foreach ($grouped as $logicalPath => $set) {
            $pages[$logicalPath] = $this->makePage($logicalPath, $set);
        }

        return $pages;
    }

    /**
     * @param array<ParsedFile> $files
     */
    private function makePage(string $logicalPath, array $files): ?Page
    {
        $page = match(count($files)) {
            0 => null,
            1 => new PageFile($files[0]),
            default => new AggregatePage($logicalPath, array_map(static fn(ParsedFile $f) => new PageFile($f), $files)),
        };

        if (($files[0] ?? null)?->isFolder) {
            $data = $this->loadFolder($logicalPath);
            return $data ? new Folder($data, $this, $page) : null;
        }

        return $page;
    }
}
