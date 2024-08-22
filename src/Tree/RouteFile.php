<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

readonly class RouteFile
{
    public function __construct(
        public string $physicalPath,
        public string $logiclPath,
        public string $ext,
    ) {}
}