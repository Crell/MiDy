<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\PageTree\FolderDef;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;

class ParsedFolder
{
    public string $parent {
        get => $this->logicalPath === '/' ? '' : dirname($this->logicalPath);
    }

    public function __construct(
        public readonly string $logicalPath,
        public readonly string $physicalPath,
        public readonly int $mtime,
        public readonly bool $flatten,
        public readonly string $title,
    ) {}
}
