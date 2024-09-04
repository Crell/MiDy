<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

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

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): RouteFile|FileInterpreterError
    {
        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . $basename;

        // Static files have no frontmatter to parse.
        /// @todo Except HTML files, where maybe we can pull the title at least.
        $frontmatter = new MiDyBasicFrontMatter(title: ucfirst($basename));

        return new RouteFile(
            physicalPath: $fileInfo->getPathname(),
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            mtime: $fileInfo->getMTime(),
            frontmatter: $frontmatter,
        );
    }
}
