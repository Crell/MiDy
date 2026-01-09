<?php

declare(strict_types=1);

namespace Crell\MiDy\Config;

use Crell\Config\Config;

/**
 * @codeCoverageIgnore
 */
#[Config('markdown-latte')]
readonly class MarkdownLatteConfiguration
{
    public function __construct(
        public string $defaultPageTemplate = 'page.latte',
        // Be sure to copy this file from vendor/scrivo/highlight.php/styles
        // to your docroot. The path here is relative to the routes directory.
        public string $codeThemeStyles = 'styles/darcula.css',
    ) {}
}
