<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTree\BasicPageInformation;
use Crell\MiDy\PageTree\FileInterpreter\FileInterpreter;
use Crell\MiDy\PageTree\FileInterpreter\FileInterpreterError;
use Crell\MiDy\PageTree\PageFile;
use Crell\MiDy\PageTree\PageInformation;

class StaticFileParser implements FileParser
{
    public array $supportedExtensions {
        get => array_keys($this->config->allowedExtensions);
    }

    public function __construct(
        private readonly StaticRoutes $config,
    ) {}

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): ParsedFile|FileParserError
    {
        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . $basename;

        // Static files have no frontmatter to parse.
        /// @todo Except HTML files, where maybe we can pull the title at least.
        $frontmatter = new BasicPageInformation(title: ucfirst($basename));

        return new ParsedFile(
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            physicalPath: $fileInfo->getPathname(),
            mtime: $fileInfo->getMTime(),
            title: $basename,
            folder: $parentLogicalPath,
            order: 0,
            hidden: true,
            routable: true,
            publishDate: new \DateTimeImmutable('@' . $fileInfo->getMTime()),
            lastModifiedDate: new \DateTimeImmutable('@' . $fileInfo->getMTime()),
            frontmatter: $frontmatter,
            summary: '',
        );
    }
}

