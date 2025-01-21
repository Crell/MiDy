<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Traversable;

readonly class Pagination implements \IteratorAggregate
{
    public function __construct(
        public int $total,
        public int $pageSize,
        public int $pageCount,
        public int $pageNum,
        public PageSet $items,
    ) {}

    public function getIterator(): Traversable
    {
        return $this->items;
    }
}
