<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

class Folder implements \IteratorAggregate, PageSet, Page
{
    use FolderIndexPage;

    public private(set) string $logicalPath {
        get => $this->logicalPath ??= $this->parsedFolder->logicalPath;
    }

    // @todo This probably should go away, in favor of always getting data by
    //   pagination.  Unclear if that means iteration goes away.
    private PageSet $children {
        get => $this->children ??= new BasicPageSet($this->pageTree->folderAllPages($this->logicalPath));
    }

    public function __construct(
        private readonly ParsedFolder $parsedFolder,
        private readonly PageTree $pageTree,
        private readonly ?Page $indexPage = null,
    ) {}

    public function count(): int
    {
        return count($this->children);
    }

    public function getIterator(): PageSet
    {
        return $this->children;
    }

    public function limit(int $limit): PageSet
    {
        return $this->children(pageSize: $limit)->items;
    }

    public function get(string $name): ?Page
    {
        // @todo Replace this with a dedicated query, probably, for performance.
        return $this->children->get($name);
    }

    public function filter(\Closure $filter, int $pageSize = PageCacheDB::DefaultPageSize, int $pageNum = 1): Pagination
    {
        return $this->children->filter($filter, $pageSize, $pageNum);
    }

    public function filterAnyTag(array $tags, int $pageSize = PageCacheDB::DefaultPageSize, int $pageNum = 1): Pagination
    {
        return $this->pageTree->folderAnyTag($this->logicalPath, $tags, $pageSize, $pageNum);
    }

    public function __debugInfo(): ?array
    {
        return [
            'logicalPath' => $this->logicalPath,
            'physicalPath' => $this->parsedFolder->physicalPath,
            'mtime' => $this->parsedFolder->mtime,
        ];
    }
}
