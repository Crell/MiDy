<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

readonly class RouteFile implements Linkable
{
    public function __construct(
        public string $physicalPath,
        public string $logicalPath,
        public string $ext,
    ) {}

    // @todo Make this better.
    public function title(): string
    {
        return ucfirst(pathinfo($this->logicalPath, PATHINFO_BASENAME));
    }

    public function path(): string
    {
        return $this->logicalPath;
    }
}
