<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

/**
 * @todo Eventually we'll need to parse for frontmatter somehow, but for now, skip and treat static.
 */
class LatteFileInterpreter implements FileInterpreter
{
    public function supportedExtensions(): array
    {
        return ['latte'];
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
