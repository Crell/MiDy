<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\PageTree\SortOrder;

readonly class FolderDef
{
    public function __construct(
        public SortOrder $order = SortOrder::Asc,
        public bool $flatten = false,
        public bool $hidden = false,
    ) {}
}