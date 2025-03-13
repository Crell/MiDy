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

    public PhysicalPath $physicalPath {
        set(PhysicalPath|string $value) => PhysicalPath::create($value);
    }

    public function __construct(
        LogicalPath|string $logicalPath,
        PhysicalPath|string $physicalPath,
        public readonly int $mtime,
        public readonly bool $flatten,
        public readonly string $title,
    ) {
        $this->logicalPath = $logicalPath;
        $this->physicalPath = $physicalPath;
    }
}
