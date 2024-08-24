<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

class StaticFileInterpreter implements FileInterpreter
{
    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath): RouteFile
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
