<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

class Folder implements \IteratorAggregate, PageSet, Page
{
    use FolderIndexPage;

    public private(set) string $logicalPath {
        get => $this->logicalPath ??= $this->parsedFolder->logicalPath;
    }

    private PageSet $children {
        get => $this->children ??= new BasicPageSet($this->pageTree->pages($this->logicalPath));
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
        return $this->paginate($limit)->items;
    }

    public function paginate(int $pageSize, int $pageNum = 1): Pagination
    {
        return $this->pageTree->paginateFolder($this->logicalPath, $pageSize, $pageNum);
    }

    public function all(): PageSet
    {
        return $this->children;
    }

    public function filter(\Closure $filter): PageSet
    {
        return $this->children->filter($filter);
    }

    public function get(string $name): ?Page
    {
        return $this->children->get($name);
    }

    public function filterAnyTag(array $tags, int $pageSize = PageCacheDB::DefaultPageSize, int $pageNum = 1): PageSet
    {
        return $this->pageTree->pagesAnyTag($this->logicalPath, $tags)->items;
    }

    public function filterAllTags(array $tags, int $pageSize = PageCacheDB::DefaultPageSize, int $pageNum = 1): PageSet
    {
        return $this->pageTree->pagesAllTags($this->logicalPath, $tags, $pageSize, $pageNum)->items;
    }

    public function __debugInfo(): ?array
    {
        return [
            'logicalPath' => $this->logicalPath,
            'physicalPath' => $this->parsedFolder->physicalPath,
            'mtime' => $this->parsedFolder->mtime,
            'children count' => count($this->children),
        ];
    }
}
