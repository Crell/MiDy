<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Traversable;

class FolderData implements \Countable, \IteratorAggregate
{
    public function __construct(
        protected readonly string $physicalPath,
        protected readonly string $logicalPath,
        public readonly array $children,
    ) {}

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->children);
    }

    public function child(string $name): Page|FolderData|null
    {
        var_dump($this->children);
    }

    public function count(): int
    {
        return count($this->children);
    }
}
