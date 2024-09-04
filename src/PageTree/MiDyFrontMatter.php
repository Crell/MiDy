<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class MiDyFrontMatter
{
    public function __construct(
        public readonly string $title = '',
        public readonly string $summary = '',
        public readonly array $tags = [],
    ) {
    }
}