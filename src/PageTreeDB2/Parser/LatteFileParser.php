<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2\Parser;

use Crell\MiDy\PageTree\BasicPageInformation;
use Crell\MiDy\PageTreeDB2\ParsedFile;
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

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): ParsedFile|FileParserError
    {
        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . $basename;

        $frontmatter = $this->extractFrontMatter(file_get_contents($fileInfo->getPathname()));

        $frontmatter ??= new BasicPageInformation(title: ucfirst($basename));

        return new ParsedFile(
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            physicalPath: $fileInfo->getPathname(),
            mtime: $fileInfo->getMTime(),
            title: $frontmatter->title,
            folder: $parentLogicalPath,
            order: 0,
            hidden: $frontmatter->hidden,
            routable: true,
            publishDate: new \DateTimeImmutable('@' . $fileInfo->getMTime()),
            lastModifiedDate: new \DateTimeImmutable('@' . $fileInfo->getMTime()),
            frontmatter: $frontmatter,
            summary: $frontmatter->summary,
            pathName: $basename,
        );
    }

    /**
     * Extract frontmatter from the template file, if any.
     *
     * @todo This can probably be done in a less hacky way.
     */
    private function extractFrontMatter(string $source): ?BasicPageInformation
    {
        $frontmatter = str_extract_between($source, self::FrontMatterStart,self::FrontMatterEnd);
        if ($frontmatter === null) {
            return null;
        }

        return $this->serde->deserialize($frontmatter, from: 'yaml', to: BasicPageInformation::class);
    }

}
