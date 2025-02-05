<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\PageTree\Model\ParsedFrontmatter;

interface FileParser
{
    /**
     * @return array<string>
     *     A list of supported file extensions.
     */
    public array $supportedExtensions { get; }

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): ParsedFrontmatter|FileParserError;
}
