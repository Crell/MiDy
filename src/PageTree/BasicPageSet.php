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

    public function getIterator(): Traversable
    {
        /** @var FolderRef|Page $child */
        foreach ($this->visibleChildren() as $child) {
            yield match (get_class($child)) {
                Page::class => $child,
            };
        }
    }

    /**
     * Iterates just the visible (non-hidden) children.
     */
    public function visibleChildren(): iterable
    {
        return new \CallbackFilterIterator(new \ArrayIterator($this->children), $this->visibilityFilter(...));
    }

    private function visibilityFilter(Page|FolderRef $page): bool
    {
        // @todo This is a sign we need a hideable interface. Probably post 8.4 so we can use interface properties...
        return !$page->hidden;
    }

    public function all(): iterable
    {
        return new \ArrayIterator($this->children);
    }

    public function limit(int $count): static
    {
        if (count($this->children) <= $count) {
            return $this;
        }

        $limitedChildren = array_chunk($this->children, $count, preserve_keys: true);

        return new self($limitedChildren);
    }

    public function paginate(int $pageSize, int $pageNum = 1): Pagination
    {
        $allPages = $this->children;
        // Exclude the folder itself from pagination.
        unset($allPages[Folder::IndexPageName]);
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
}