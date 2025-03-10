<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class ParsedFolder
{
    public LogicalPath $parent {
        get => $this->logicalPath->parent;
    }

    public LogicalPath $logicalPath {
        set(LogicalPath|string $value) => LogicalPath::create($value);
    }

    public function __construct(
        LogicalPath|string $logicalPath,
        public readonly string $physicalPath,
        public readonly int $mtime,
        public readonly bool $flatten,
        public readonly string $title,
    ) {
        $this->logicalPath = $logicalPath;
    }
}
