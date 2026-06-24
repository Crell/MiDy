<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\PageTree\LinkFrontmatter;
use Crell\MiDy\PageTree\LogicalPath;
use Crell\MiDy\PageTree\ParsedFrontmatter;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;
use Symfony\Component\Yaml\Exception\ParseException;

class LinkFileParser implements FileParser
{
    public private(set) array $supportedExtensions = ['link'];

    public function __construct(
        private readonly Serde $serde = new SerdeCommon(),
    ) {}

    public function map(\SplFileInfo $fileInfo, LogicalPath $parentLogicalPath, string $basename): ParsedFrontmatter|FileParserError
    {
        try {
            $file = file_get_contents($fileInfo->getPathname());
            $frontmatter = $this->serde->deserialize($file, from: 'yaml', to: LinkFrontmatter::class);
            return $frontmatter;
        } catch (ParseException $e) {
            return FileParserError::FileNotSupported;
        }
    }
}
