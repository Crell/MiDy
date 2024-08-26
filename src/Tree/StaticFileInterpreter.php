<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Crell\MiDy\Config\StaticRoutes;

readonly class StaticFileInterpreter implements FileInterpreter
{

    public function __construct(
        private StaticRoutes $config,
    ) {}

    public function supportedExtensions(): array
    {
        return array_keys($this->config->allowedExtensions);
    }

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath): RouteFile|FileInterpreterError
    {
        // SPL is so damned stupid...
        $basename = $fileInfo->getBasename('.' . $fileInfo->getExtension());

        // @todo This gets more flexible.
        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . $basename;

        return new RouteFile(
            physicalPath: $fileInfo->getPathname(),
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            mtime: $fileInfo->getMTime(),
            title: ucfirst($basename),
            order: 0,
        );
    }
}
