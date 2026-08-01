<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\PageTree\BasicParsedFrontmatter;
use Crell\MiDy\PageTree\SortOrder;

class FolderDef
{
    public SortOrder $order = SortOrder::Asc {
        set(SortOrder|string $value) => is_string($value) ? SortOrder::fromString($value) : $value;
    }

    public function __construct(
        SortOrder|string $order = SortOrder::Asc,
        public readonly bool $flatten = false,
        public readonly bool $hidden = false,
        public readonly BasicParsedFrontmatter $defaults = new BasicParsedFrontmatter(),
    ) {
        $this->order = $order;
    }
}
