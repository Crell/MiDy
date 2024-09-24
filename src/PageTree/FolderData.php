<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Traversable;

class FolderData implements \Countable, \IteratorAggregate
{
    public function __construct(
        protected readonly string $physicalPath,
        protected readonly string $logicalPath,
        public readonly array $children,
    ) {}

    /**
     * Iterates all children, visible or not.
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->children);
    }

    public function count(): int
    {
        return count($this->children);
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
        if ($page instanceof Page) {
            return !$page->hidden();
        }
        return !$page->hidden;
    }
}
