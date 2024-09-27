<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\FileInterpreter;

use Crell\MiDy\PageTree\PageFile;

interface FileInterpreter
{
    /**
     * @return array<string>
     *     A list of supported file extensions.
     */
    public function supportedExtensions(): array;

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): PageFile|FileInterpreterError;
}