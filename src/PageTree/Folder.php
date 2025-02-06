<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

/**
 * A logical representation of a Folder.
 *
 * If the Folder contains an index page, then it may also be
 * treated as a Page.  All Page behavior will be forwarded to
 * the index page.
 */
class Folder implements \IteratorAggregate, Page
{
    use FolderIndexPage;

    public private(set) string $logicalPath {
        get => $this->logicalPath ??= $this->parsedFolder->logicalPath;
    }

    public function __construct(
        private readonly ParsedFolder $parsedFolder,
        private readonly PageTree $pageTree,
        private readonly ?Page $indexPage = null,
    ) {}

    public function children(
        bool $deep = false,
        bool $includeHidden = false,
        bool $routableOnly = true,
        array $anyTag = [],
        ?\DateTimeInterface $publishedBefore = new \DateTimeImmutable(),
        array $orderBy = [],
        int $pageSize = PageRepo::DefaultPageSize,
        int $pageNum = 1
    ): Pagination {
        return $this->pageTree->queryPages(
            folder: $this->logicalPath,
            deep: $deep,
            includeHidden: $includeHidden,
            routableOnly: $routableOnly,
            anyTag: $anyTag,
            publishedBefore: $publishedBefore,
            orderBy: $orderBy,
            pageSize: $pageSize,
            pageNum: $pageNum,
        );
    }

    public function getIterator(): Pagination
    {
        return $this->children();
    }

    public function limit(int $limit): PageSet
    {
        return $this->children(pageSize: $limit)->items;
    }

    public function get(string $name): ?Page
    {
        return $this->pageTree->page(rtrim($this->logicalPath, '/') . '/' . $name);
    }

    public function filter(\Closure $filter, int $pageSize = PageRepo::DefaultPageSize, int $pageNum = 1): Pagination
    {
        return $this
            ->children(pageSize: $pageSize, pageNum: $pageNum)
            ->items
            ->filter($filter, $pageSize, $pageNum);
    }

    public function filterAnyTag(array $tags, int $pageSize = PageRepo::DefaultPageSize, int $pageNum = 1): Pagination
    {
        return $this->children(anyTag: $tags, pageSize: $pageSize, pageNum: $pageNum);
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
