<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Traversable;

readonly class FolderRef
{
    public function __construct(
        public string $physicalPath,
        public string $logicalPath,
    ) {}
}
