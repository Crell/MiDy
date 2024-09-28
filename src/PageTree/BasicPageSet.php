<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Traversable;

/**
 * Possibly the only type of page set, but we need it separate from the interface for flexibility.
 */
readonly class BasicPageSet implements PageSet, \IteratorAggregate
{
    /**
     * @param array<string, Page> $children
     */
    public function __construct(
        private array $children,
    ) {}

    public function count(): int
    {
        return count($this->children);
    }

    /**
     * Iterates just the visible (non-hidden) children.
     */
    public function getIterator(): Traversable
    {
        return new \CallbackFilterIterator(new \ArrayIterator($this->children), $this->visibilityFilter(...));
//        /** @var Page $child */
//        foreach ($this->visibleChildren() as $child) {
//            yield match (get_class($child)) {
//                Page::class => $child,
//            };
//        }
    }

    private function visibilityFilter(Hidable $page): bool
    {
        return !$page->hidden();
    }

    public function all(): iterable
    {
        return new \ArrayIterator($this->children);
    }

    public function limit(int $count): PageSet
    {
        if (count($this->children) <= $count) {
            return $this;
        }

        $limitedChildren = array_chunk($this->children, $count, preserve_keys: true);

        return new BasicPageSet($limitedChildren);
    }

    public function paginate(int $pageSize, int $pageNum = 1): Pagination
    {
        $allPages = $this->children;
        $pageChunks = array_chunk($allPages, $pageSize, preserve_keys: true);

        return new Pagination(
            total: count($allPages),
            pageSize: $pageSize,
            pageCount: count($pageChunks),
            pageNum: $pageNum,
            // -1, because $pageChunks is 0-based.
            items: new BasicPageSet($pageChunks[$pageNum - 1]),
        );
    }

    public function filter(\Closure $filter): PageSet
    {
        return new BasicPageSet(iterator_to_array(new \CallbackFilterIterator(new \ArrayIterator($this->children), $filter)));
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

        /** @var ?Page $files */
        $files = $this->children[$info['filename']] ?? null;
        if ($info['extension']) {
            return $files?->variant($info['extension']);
        }
        return $files;
    }
}
