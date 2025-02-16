<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\PageTree\BasicParsedFrontmatter;
use Crell\MiDy\PageTree\ParsedFrontmatter;
use Dom\HTMLDocument;

/**
 * A special variant of StaticFile Parser that only applies to HTML files.
 *
 * It allows us to pull select information from the HTML document itself,
 * and marks it visible in the menu rather than hidden.
 */
class HtmlFileParser implements FileParser
{
    public private(set) array $supportedExtensions = ['html'];

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): ParsedFrontmatter|FileParserError
    {
        $html = HTMLDocument::createFromFile($fileInfo->getPathname());

        // Static files have no frontmatter to parse.
        return new BasicParsedFrontmatter(
            title: $html->title,
            hidden: false,
        );
    }
}
