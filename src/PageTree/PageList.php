<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

readonly class PageList implements \Countable, \IteratorAggregate
{
    public function __construct(
        private array $nodes,
    ) {
    }

    public function count(): int
    {
        return count($this->nodes);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->nodes);
    }

    public function get(string $name): Page|Folder|null
    {
        return $this->nodes[$name] ?? null;
    }
}
