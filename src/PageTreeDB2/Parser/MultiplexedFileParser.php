<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2\Parser;

use Crell\MiDy\PageTreeDB2\ParsedFile;

class MultiplexedFileParser implements FileParser
{
    public array $supportedExtensions {
        get => array_keys($this->parsers);
    }

    /**
     * @var FileParser
     */
    private array $parsers = [];

    public function addParser(FileParser $parser): void
    {
        foreach ($parser->supportedExtensions as $ext) {
            $this->parsers[$ext][] = $parser;
        }
    }

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): ParsedFile|FileParserError
    {
        /** @var FileParser $parser */
        foreach ($this->parsers[$fileInfo->getExtension()] ?? [] as $parser) {
            if (($routeFile = $parser->map($fileInfo, $parentLogicalPath, $basename)) instanceof ParsedFile) {
                return $routeFile;
            }
        }

        return FileParserError::FileNotSupported;
    }
}
