<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\PageTree\Model\PageRead;
use Traversable;

readonly class QueryResult implements \Countable, \IteratorAggregate
{
    /**
     * @param array<PageRead> $pages
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
