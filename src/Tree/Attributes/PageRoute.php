<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree\Attributes;

#[\Attribute]
readonly class PageRoute
{
    public function __construct(
        public ?string $slug = null,
        public ?string $title = null,
    ) {}
}
