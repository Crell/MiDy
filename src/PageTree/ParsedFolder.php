<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class ParsedFolder
{
    public string $parent {
        get => dirname($this->logicalPath);
    }

    public function __construct(
        public readonly string $logicalPath,
        public readonly string $physicalPath,
        public readonly int $mtime,
        public readonly bool $flatten,
        public readonly string $title,
    ) {}
}
