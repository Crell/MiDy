<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\PageTree\LogicalPath;
use Crell\MiDy\PageTree\ParsedFrontmatter;

class MultiplexedFileParser implements FileParser
{
    public array $supportedExtensions {
        get => array_keys($this->parsers);
    }

    /**
     * @var array<string, array<FileParser>>
     *
     * A map from a file extension to a list of parsers that want to try parsing it.
     */
    private array $parsers = [];

    public function addParser(FileParser $parser): void
    {
        foreach ($parser->supportedExtensions as $ext) {
            $this->parsers[$ext][] = $parser;
        }
    }

    public function map(\SplFileInfo $fileInfo, LogicalPath $parentLogicalPath, string $basename): ParsedFrontmatter|FileParserError
    {
        /** @var FileParser $parser */
        foreach ($this->parsers[$fileInfo->getExtension()] ?? [] as $parser) {
            if (($frontmatter = $parser->map($fileInfo, $parentLogicalPath, $basename)) instanceof ParsedFrontmatter) {
                return $frontmatter;
            }
        }

        return FileParserError::FileNotSupported;
    }
}
