<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Traversable;

class Pagination implements \IteratorAggregate
{
    public int $lastPageNum {
        get => $this->lastPageNum ??= (int)ceil($this->total / $this->pageSize);
    }

    public ?int $nextPageNum {
        get => $this->nextPageNum ??= $this->pageNum < $this->lastPageNum
            ? $this->pageNum + 1
            : null;
    }

    public ?int $previousPageNum {
        get => $this->previousPageNum ??= $this->pageNum > 1
            ? $this->pageNum - 1
            : null;
    }

    public function __construct(
        public readonly int $total,
        public readonly int $pageSize,
        public readonly int $pageCount,
        public readonly int $pageNum,
        public readonly PageSet $items,
    ) {}

    public function getIterator(): Traversable
    {
        return $this->items;
    }
}
