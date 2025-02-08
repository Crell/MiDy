<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Latte;

use Crell\MiDy\PageTree\Page;
use Latte\Extension;

class PageTreeExtension extends Extension
{
    public function __construct(private readonly string $baseUrl) {}

    public function getFunctions(): array
    {
        return [
            'pageUrl' => $this->pageUrl(...),
            // ...
        ];
    }

    public function pageUrl(Page $page): string
    {
        return sprintf("%s%s", rtrim($this->baseUrl, '/'), $page->path);
    }
}
