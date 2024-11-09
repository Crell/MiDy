<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

class MultiplexedFileParser implements FileParser
{
    /**
     * @var array<string, array<FileParser>>
     */
    private array $parsers = [];

    public function addParser(FileParser $interpreter): void
    {
        foreach ($interpreter->supportedExtensions() as $ext) {
            $this->parsers[$ext][] = $interpreter;
        }
    }

    public function supportedExtensions(): array
    {
        return array_keys($this->parsers);
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
