<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;

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

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): RouteFile|FileInterpreterError
    {
        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . $basename;

        $frontmatter = $this->extractFrontMatter(file_get_contents($fileInfo->getPathname()));

        $frontmatter ??= new MiDyBasicFrontMatter(title: ucfirst($basename));

        return new RouteFile(
            physicalPath: $fileInfo->getPathname(),
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            mtime: $fileInfo->getMTime(),
            frontmatter: $frontmatter,
        );
    }

    /**
     * Extract frontmatter from the template file, if any.
     *
     * @todo This can probably be done in a less hacky way.
     */
    private function extractFrontMatter(string $source): ?MiDyBasicFrontMatter
    {
        $start = strpos($source, self::FrontMatterStart);
        if ($start === false) {
            return null;
        }

        $end = strpos($source, self::FrontMatterEnd, $start);
        if ($end === false) {
            return null;
        }

        // @todo I have no idea why the -1 is needed here...
        $frontmatter = substr($source, $start + strlen(self::FrontMatterStart), $end - $start - strlen(self::FrontMatterEnd) - 1);
        return $this->serde->deserialize($frontmatter, from: 'yaml', to: MiDyBasicFrontMatter::class);
    }

}
