<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2\Parser;

use Crell\MiDy\PageTreeDB2\ParsedFile;

interface FileParser
{
    /**
     * @return array<string>
     *     A list of supported file extensions.
     */
    public array $supportedExtensions { get; }

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): ParsedFile|FileParserError;
}
