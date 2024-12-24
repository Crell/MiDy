<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Traversable;

/**
 * Possibly the only type of page set, but we need it separate from the interface for flexibility.
 */
readonly class BasicPageSet implements PageSet, \IteratorAggregate
{
    /**
     * @param array<string, Page> $pages
     */
    public function __construct(
        private iterable $pages,
    ) {}

    public function count(): int
    {
        return count($this->pages);
    }

    /**
     * Iterates just the visible (non-hidden) children.
     */
    public function getIterator(): \CallbackFilterIterator
    {
        return new \CallbackFilterIterator(new \IteratorIterator($this->all()), $this->visibilityFilter(...));
    }

    private function visibilityFilter(Hidable $page): bool
    {
        return !$page->hidden;
    }

    public function all(): \Traversable
    {
        return is_array($this->pages) ? new \ArrayIterator($this->pages) : $this;
    }

    public function limit(int $count): PageSet
    {
        if (count($this->pages) <= $count) {
            return $this;
        }

        $limitedChildren = array_chunk(iterator_to_array($this->pages), $count, preserve_keys: true);

        return new BasicPageSet($limitedChildren);
    }

//    public function paginate(int $pageSize, int $pageNum = 1): Pagination
//    {
//        $allPages = iterator_to_array($this->children);
//        $pageChunks = array_chunk($allPages, $pageSize, preserve_keys: true);
//
//        return new Pagination(
//            total: count($allPages),
//            pageSize: $pageSize,
//            pageCount: count($pageChunks),
//            pageNum: $pageNum,
//            // -1, because $pageChunks is 0-based.
//            items: new BasicPageSet($pageChunks[$pageNum - 1]),
//        );
//    }

    public function filter(\Closure $filter): PageSet
    {
        return new BasicPageSet(iterator_to_array(new \CallbackFilterIterator(new \IteratorIterator($this), $filter)));
    }

    public function filterAnyTag(string ...$tags): PageSet
    {
        return $this->filter(static fn (Page $p) => $p->hasAnyTag(...$tags));
    }

    public function filterAllTags(string ...$tags): PageSet
    {
        return $this->filter(static fn (Page $p) => $p->hasAllTags(...$tags));
    }

    public function get(string $name): ?Page
    {
        $info = pathinfo($name);

        /** @var array<Page> $children */
        $children = iterator_to_array($this->pages);

        // @todo This is probably stupidly slow.
        $key = array_find_key($children, static fn(Page $p) => $p->name === $name);
        $page = $children[$key] ?? null;

        if ($info['extension'] ?? false) {
            return $page?->variant($info['extension']);
        }
        return $page;
    }
}
