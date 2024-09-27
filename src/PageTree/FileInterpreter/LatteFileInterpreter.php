<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\FileInterpreter;

use Crell\MiDy\PageTree\BasicPageInformation;
use Crell\MiDy\PageTree\PageFile;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;

use function Crell\MiDy\str_extract_between;

/**
 * @todo Eventually we'll need to parse for frontmatter somehow, but for now, skip and treat static.
 */
class LatteFileInterpreter implements FileInterpreter
{
    private const string FrontMatterStart = "{*---\n";
    private const string FrontMatterEnd = "---*}";

    public function __construct(
        protected readonly Serde $serde = new SerdeCommon(),
    ) {}

    public function supportedExtensions(): array
    {
        return ['latte'];
    }

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): PageFile|FileInterpreterError
    {
        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . $basename;

        $frontmatter = $this->extractFrontMatter(file_get_contents($fileInfo->getPathname()));

        $frontmatter ??= new BasicPageInformation(title: ucfirst($basename));

        return new PageFile(
            physicalPath: $fileInfo->getPathname(),
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            mtime: $fileInfo->getMTime(),
            info: $frontmatter,
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
