<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\MarkdownDeserializer\MarkdownError;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\PageTree\LogicalPath;
use Crell\MiDy\PageTree\ParsedFrontmatter;

class MarkdownLatteFileParser implements FileParser
{
    public private(set) array $supportedExtensions = ['md'];

    public function __construct(
        private MarkdownPageLoader $loader,
    ) {}

    public function map(\SplFileInfo $fileInfo, LogicalPath $parentLogicalPath, string $basename): ParsedFrontmatter|FileParserError
    {
        $page = $this->loader->load($fileInfo->getPathname());

        if ($page === MarkdownError::FileNotFound) {
            return FileParserError::FileNotSupported;
        }

        return $page;
    }
}
