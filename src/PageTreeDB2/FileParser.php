<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

interface FileParser
{
    /**
     * @return array<string>
     *     A list of supported file extensions.
     */
    public function supportedExtensions(): array;

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): ParsedFile|FileParserError;
}
