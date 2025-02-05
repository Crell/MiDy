<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\PageTree\Model\PageRead;

/**
 * Possibly the only type of page set, but we need it separate from the interface for flexibility.
 */
class BasicPageSet implements PageSet, \IteratorAggregate
{
    /**
     * @var array<Page>
     */
    private array $materializedPages {
        get => $this->materializedPages ??= iterator_to_array($this->pages);
    }

    /**
     * @param array<string, PageRead> $pages
     */
    public function __construct(
        private readonly iterable $pages,
    ) {}

    public function count(): int
    {
        return count($this->materializedPages);
    }

    /**
     * Iterates just the visible (non-hidden) children.
     */
    public function getIterator(): \Traversable
    {
        return $this->pages instanceof \Traversable ? $this->pages : new \ArrayIterator($this->pages);
    }

    public function all(): \Traversable
    {
        return is_array($this->pages) ? new \ArrayIterator($this->pages) : $this;
    }

    /**
     * Ideally, the data set will be limited in SQL before we even get to this point.
     * But if not, runtime limiting is possible.
     */
    public function limit(int $limit): PageSet
    {
        if (count($this->pages) <= $limit) {
            return $this;
        }

        $limitedChildren = array_chunk($this->materializedPages, $limit, preserve_keys: true);

        return new BasicPageSet($limitedChildren);
    }

    private function paginateBuilder(array $pages, int $pageSize, int $pageNum = 1): Pagination
    {
        // @todo This likely won't scale well, but works for the moment.

        $pageChunks = array_chunk($pages, $pageSize, preserve_keys: true);

        return new Pagination(
            total: count($pages),
            pageSize: $pageSize,
            pageCount: count($pageChunks),
            pageNum: $pageNum,
            // -1, because $pageChunks is 0-based.
            items: new BasicPageSet($pageChunks[$pageNum - 1]),
        );
    }

    public function filter(\Closure $filter, int $pageSize = PageRepo::DefaultPageSize, int $pageNum = 1): Pagination
    {
        $pages = new \CallbackFilterIterator(new \IteratorIterator($this), $filter);
        return $this->paginateBuilder(iterator_to_array($pages), $pageSize, $pageNum);
    }

    public function filterAnyTag(array $tags, int $pageSize = PageRepo::DefaultPageSize, int $pageNum = 1): Pagination
    {
        return $this->filter(static fn (Page $p) => $p->hasAnyTag(...$tags), $pageSize, $pageNum);
    }

    public function get(string $name): ?Page
    {
        $info = pathinfo($name);

        // @todo This is probably stupidly slow.
        $key = array_find_key($this->materializedPages, static fn(Page $p) => $p->name === $name);
        $page = $this->materializedPages[$key] ?? null;

        if ($info['extension'] ?? false) {
            return $page?->variant($info['extension']);
        }
        return $page;
    }
}
