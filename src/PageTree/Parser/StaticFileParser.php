<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTree\BasicPageInformation;
use Crell\MiDy\PageTree\Model\BasicParsedFrontmatter;
use Crell\MiDy\PageTree\Model\ParsedFileInformation;
use Crell\MiDy\PageTree\Model\ParsedFrontmatter;
use Crell\MiDy\PageTree\ParsedFile;

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

