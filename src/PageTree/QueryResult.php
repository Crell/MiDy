<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Traversable;

readonly class QueryResult implements \Countable, \IteratorAggregate
{
    /**
     * @param array<PageRecord> $pages
     */
    public function __construct(
        public int $total,
        public array $pages,
    ) {}

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->pages);
    }

    public function count(): int
    {
        return count($this->pages);
    }
}
