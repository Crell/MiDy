<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\PageTree\BasicParsedFrontmatter;
use Crell\MiDy\PageTree\LogicalPath;
use Crell\MiDy\PageTree\ParsedFrontmatter;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;

use function Crell\MiDy\str_extract_between;

class LatteFileParser implements FileParser
{
    private const string FrontMatterStart = "{*---\n";
    private const string FrontMatterEnd = "---*}";

    public private(set) array $supportedExtensions = ['latte'];

    public function __construct(
        protected readonly Serde $serde = new SerdeCommon(),
    ) {}

    public function map(\SplFileInfo $fileInfo, LogicalPath $parentLogicalPath, string $basename): ParsedFrontmatter|FileParserError
    {
        $frontmatter = $this->extractFrontMatter(file_get_contents($fileInfo->getPathname()));

        $frontmatter ??= new BasicParsedFrontmatter(title: ucfirst($basename));

        return $frontmatter;
    }

    /**
     * Extract frontmatter from the template file, if any.
     *
     * @todo This can probably be done in a less hacky way.
     */
    private function extractFrontMatter(string $source): ?ParsedFrontmatter
    {
        $frontmatter = str_extract_between($source, self::FrontMatterStart,self::FrontMatterEnd);
        if ($frontmatter === null) {
            return null;
        }

        return $this->serde->deserialize($frontmatter, from: 'yaml', to: BasicParsedFrontmatter::class);
    }
}
