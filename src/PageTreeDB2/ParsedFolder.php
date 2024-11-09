<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\PageTree\FolderDef;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;

readonly class ParsedFolder
{
    public function __construct(
        public string $logicalPath,
        public string $physicalPath,
        public int $mtime,
        public bool $flatten,
        public string $title,
    ) {}
}
