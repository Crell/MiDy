<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\PageTreeDB2\SortOrder;

readonly class FolderDef
{
    public function __construct(
        public SortOrder $order = SortOrder::Asc,
        public bool $flatten = false,
        public bool $hidden = false,
    ) {}
}