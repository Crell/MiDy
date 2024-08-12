<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

readonly class PageList implements \Countable, \IteratorAggregate
{
    public function __construct(
        private array $pages = [],
    ) {}

    public function count(): int
    {
        return count($this->pages);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->pages);
    }

    public function get(string $name): Page|Folder|null
    {
        return $this->pages[$name] ?? null;
    }
}
