<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

/**
 * A logical representation of a Folder.
 *
 * If the Folder contains an index page, then it may also be
 * treated as a Page.  All Page behavior will be forwarded to
 * the index page.
 *
 * @implements \IteratorAggregate<Page>
 */
class Folder implements \IteratorAggregate, Page
{
    use FolderIndexPage;

    private(set) LogicalPath $logicalPath {
        get => $this->logicalPath ??= $this->parsedFolder->logicalPath;
    }

    public string $folder {
        get => (string)$this->logicalPath;
    }

    public function __construct(
        private readonly ParsedFolder $parsedFolder,
        private readonly PageTree $pageTree,
        private readonly ?Page $indexPage = null,
    ) {}

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
    public function children(
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
            exclude: $exclude,
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
        return $this->pageTree->page($this->logicalPath->concat($name));
    }

    public function filter(\Closure $filter, int $pageSize = PageCache::DefaultPageSize, int $pageNum = 1): Pagination
    {
        return $this
            ->children(pageSize: $pageSize, pageNum: $pageNum)
            ->items
            ->filter($filter, $pageSize, $pageNum);
    }

    /**
     * @param list<string> $tags
     *   A list of tags for which to search.  A page will match if it has at least
     *   one of these.
     */
    public function filterAnyTag(array $tags, int $pageSize = PageCache::DefaultPageSize, int $pageNum = 1): Pagination
    {
        return $this->children(anyTag: $tags, pageSize: $pageSize, pageNum: $pageNum);
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'logicalPath' => $this->logicalPath,
            'physicalPath' => $this->parsedFolder->physicalPath,
            'mtime' => $this->parsedFolder->mtime,
        ];
    }
}
