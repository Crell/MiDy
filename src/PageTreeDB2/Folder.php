<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Traversable;

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

    public function limit(int $limit, int $offset = 0): PageSet
    {
        return new BasicPageSet($this->pageTree->pages($this->logicalPath, $limit, $offset));
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

    public function filterAnyTag(string ...$tags): PageSet
    {
        return new BasicPageSet($this->pageTree->pagesAnyTag($this->logicalPath, $tags));
    }

    public function filterAllTags(string ...$tags): PageSet
    {
        return new BasicPageSet($this->pageTree->pagesAllTags($this->logicalPath, $tags));
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
