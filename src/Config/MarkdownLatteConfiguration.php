<?php

declare(strict_types=1);

namespace Crell\MiDy\Config;

use Crell\Config\Config;

#[Config('markdown-latte')]
readonly class MarkdownLatteConfiguration
{
    public function __construct(
        public string $defaultPageTemplate = 'page.latte',
    ) {}
}
