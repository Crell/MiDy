<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTree\BasicParsedFrontmatter;
use Crell\MiDy\PageTree\ParsedFrontmatter;

class StaticFileParser implements FileParser
{
    public array $supportedExtensions {
        // Ignore HTML files, as those get their own parser.
        get => array_diff(array_keys($this->config->allowedExtensions), ['html']);
    }

    public function __construct(
        private readonly StaticRoutes $config,
    ) {}

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): ParsedFrontmatter|FileParserError
    {
        // Static files have no frontmatter to parse.
        return new BasicParsedFrontmatter(
            title: ucfirst($basename),
            hidden: true,
        );
    }
}
