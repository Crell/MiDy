<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class Page extends \SplFileInfo
{
    public function __construct(
        string $filename,
        readonly public string $urlPath,
    ) {
        parent::__construct($filename);
    }

    public function title(): string
    {
        return "Untitled";
    }
}