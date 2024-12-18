<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\MarkdownDeserializer\MarkdownError;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\PageTree\FileInterpreter\FileInterpreter;
use Crell\MiDy\PageTree\FileInterpreter\FileInterpreterError;
use Crell\MiDy\PageTree\PageFile;

class MarkdownLatteFileParser implements FileParser
{
    public private(set) array $supportedExtensions = ['md'];

    public function __construct(
        private MarkdownPageLoader $loader,
    ) {}

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): ParsedFile|FileParserError
    {
        $page = $this->loader->load($fileInfo->getPathname());

        if ($page === MarkdownError::FileNotFound) {
            return FileParserError::FileNotSupported;
        }

        $frontmatter = $page->pageInformation();

        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . ($frontmatter->slug ?? $basename);

        return new ParsedFile(
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            physicalPath: $fileInfo->getPathname(),
            mtime: $fileInfo->getMTime(),
            title: $frontmatter->title,
            folder: $parentLogicalPath,
            order: 0,
            hidden: true,
            routable: true,
            publishDate: new \DateTimeImmutable('@' . $fileInfo->getMTime()),
            lastModifiedDate: new \DateTimeImmutable('@' . $fileInfo->getMTime()),
            frontmatter: $frontmatter,
            summary: '',
            pathName: $basename,
        );
    }
}