<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\FileInterpreter;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTree\BasicPageInformation;
use Crell\MiDy\PageTree\PageFile;

readonly class StaticFileInterpreter implements FileInterpreter
{
    public function __construct(
        private StaticRoutes $config,
    ) {}

    public function supportedExtensions(): array
    {
        return array_keys($this->config->allowedExtensions);
    }

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): PageFile|FileInterpreterError
    {
        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . $basename;

        // Static files have no frontmatter to parse.
        /// @todo Except HTML files, where maybe we can pull the title at least.
        $frontmatter = new BasicPageInformation(title: ucfirst($basename));

        return new PageFile(
            physicalPath: $fileInfo->getPathname(),
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            mtime: $fileInfo->getMTime(),
            info: $frontmatter,
        );
    }
}
