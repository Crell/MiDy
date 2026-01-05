<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\PageTree\Parser\Parser;

class PageTree
{
    /**
     * @var array<string, string>
     *     A map from logical folder paths to the physical paths they correspond to.
     */
    private array $mountPoints = [];

    public function __construct(
        private readonly PageCache $cache,
        private readonly Parser $parser,
        string|PhysicalPath $rootPhysicalPath,
    ) {
        $this->mount(PhysicalPath::create($rootPhysicalPath), '/');
    }

    public function mount(PhysicalPath|string $physicalPath, string|LogicalPath $logicalPath): void
    {
        $this->mountPoints[$logicalPath] = PhysicalPath::create($physicalPath);
    }

    /**
     * Returns the Folder read-object for this path.
     */
    public function folder(LogicalPath|string $logicalPath): ?Folder
    {
        $logicalPath = LogicalPath::create($logicalPath);
        $data = $this->loadFolder($logicalPath);
        return $data ? new Folder($data, $this) : null;
    }

    /**
     * Loads a single page by path.
     */
    public function page(string|LogicalPath $path): ?Page
    {
        $path = LogicalPath::create($path);

        // We don't need the folder, but this ensures
        // the folder has been parsed so that the files
        // table is populated.
        $this->folder($path->parent());

        $page = $this->cache->readPage($path);

        if (!$page) {
            return null;
        }

        $needsReindex = array_any($page->files, static fn (File $file): bool => $file->mtime < filemtime((string)$file->physicalPath));
        if ($needsReindex) {
            $this->reindexFolder(LogicalPath::create($page->folder));
            $page = $this->cache->readPage($path);
        }

        return $page;
    }

    /**
     * @param list<string> $anyTag
     *   A list of tags for which to search.  A page will match if it has at least
     *   one of these.
     * @param array<string, int> $orderBy
     *   An associative array of properties to sort by. The key is the field name,
     *   the value is either SORT_ASC or SORT_DESC, as desired. Regardless of what
     *   is provided, the sort list will be appended with: order, title, path, to
     *   ensure queries are always deterministic.
     * @param string[] $exclude
     *   An array of paths to ignore in the query results. This is mainly useful
     *   for excluding the current page from listing pages other than an index page.
     */
    public function queryPages(
        string|LogicalPath|null $folder = null,
        bool $deep = false,
        bool $includeHidden = false,
        bool $routableOnly = true,
        array $anyTag = [],
        ?\DateTimeInterface $publishedBefore = new \DateTimeImmutable(),
        array $orderBy = [],
        int $pageSize = PageCache::DefaultPageSize,
        int $pageNum = 1,
        array $exclude = [],
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
            offset: $pageSize * ($pageNum - 1),
            exclude: $exclude,
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

    public function reindexAll(string|LogicalPath $logicalRoot = '/'): void
    {
        $logicalRoot = LogicalPath::create($logicalRoot);
        $this->reindexFolder($logicalRoot);

        foreach ($this->cache->childFolders($logicalRoot) as $child) {
            $this->reindexAll($child->logicalPath);
        }
    }

    private function loadFolder(LogicalPath $logicalPath): ?ParsedFolder
    {
        $folder = $this->cache->readFolder($logicalPath);

        if (!$folder || $folder->mtime < filemtime((string)$folder->physicalPath)) {
            $folder = $this->reindexFolder($logicalPath);
        }

        return $folder;
    }

    /**
     * Re-parse a folder at a given location by reparsing its parent's contents.
     */
    private function reindexFolder(LogicalPath $logicalPath): ?ParsedFolder
    {
        // If it's one of the mount roots, just parse that directly.
        if (array_key_exists((string)$logicalPath, $this->mountPoints)) {
            $ret = $this->parser->parseFolder(PhysicalPath::create($this->mountPoints[(string)$logicalPath]), $logicalPath, $this->mountPoints);
            // In case of parser error, fail here.
            if (!$ret) {
                return null;
            }
            // In case there is another mount point that is an immediate child,
            // reindex that too so we get any index file in it.
            foreach ($this->mountPoints as $logicalMount => $physicalMount) {
                // The weak-comparison is deliberate here, because $logicalPath is a stringable object.
                if ($logicalMount != $logicalPath && dirname($logicalMount) == $logicalPath) {
                    $this->reindexFolder(LogicalPath::create($logicalMount));
                }
            }
            return $this->cache->readFolder($logicalPath);
        }

        // Otherwise, we need to get the logical parent folder and get its physical
        // path, so we know what to parse.  If the parent is not yet indexed,
        // it will get reindexed, too.
        $slug = $logicalPath->end;
        $parent = $this->loadFolder($logicalPath->parent());
        if (!$parent) {
            return null;
        }
        $ret = $this->parser->parseFolder($parent->physicalPath->concat($slug), $logicalPath, $this->mountPoints);
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
     * @return array<Page>
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
