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

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): RouteFile|FileInterpreterError
    {
        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . $basename;

        return new RouteFile(
            physicalPath: $fileInfo->getPathname(),
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            mtime: $fileInfo->getMTime(),
            title: ucfirst($basename),
        );
    }
}
