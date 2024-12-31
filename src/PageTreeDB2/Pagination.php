<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

readonly class Pagination
{
    /**
     * @param array<string, Page> $items
     */
    public function __construct(
        public int $total,
        public int $pageSize,
        public int $pageCount,
        public int $pageNum,
        public PageSet $items,
    ) {}
}
