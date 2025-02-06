<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTree\BasicParsedFrontmatter;
use Crell\MiDy\PageTree\ParsedFrontmatter;

class StaticFileParser implements FileParser
{
    public array $supportedExtensions {
        get => array_keys($this->config->allowedExtensions);
    }

    public function __construct(
        private readonly StaticRoutes $config,
    ) {}

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): ParsedFrontmatter|FileParserError
    {
        // Static files have no frontmatter to parse.
        /// @todo Except HTML files, where maybe we can pull the title at least.
        return new BasicParsedFrontmatter(
            title: ucfirst($basename),
            hidden: true,
        );
    }
}

