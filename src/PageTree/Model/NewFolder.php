<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Model;

use Crell\MiDy\PageTree\FolderIndexPage;
use Crell\MiDy\PageTree\Page;
use Crell\MiDy\PageTree\PageRepo;
use Crell\MiDy\PageTree\PageSet;
use Crell\MiDy\PageTree\PageTree;
use Crell\MiDy\PageTree\Pagination;
use Crell\MiDy\PageTree\ParsedFolder;

class NewFolder implements \IteratorAggregate, Page
{
    use NewFolderIndexPage;

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
