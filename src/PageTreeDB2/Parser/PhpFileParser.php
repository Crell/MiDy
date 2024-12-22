<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2\Parser;

use Crell\MiDy\ClassFinder;
use Crell\MiDy\PageTreeDB2\Attributes\PageRoute;
use Crell\MiDy\PageTreeDB2\ParsedFile;

class PhpFileParser implements FileParser
{
    public private(set) array $supportedExtensions = ['php'];

    public function __construct(
        private readonly ClassFinder $finder = new ClassFinder(),
    ) {}

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): ParsedFile
    {
        $physicalPath = $fileInfo->getPathname();

        $frontmatter = $this->extractFrontMatter($physicalPath);

        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . ($frontmatter->slug ?? $basename);

        return new ParsedFile(
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            physicalPath: $physicalPath,
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

    private function extractFrontMatter(string $physicalPath): PageRoute
    {
        require_once $physicalPath;
        $class = $this->finder->getClass($physicalPath);

        if (!$class) {
            return new PageRoute();
        }

        $attribs = array_map(fn(\ReflectionAttribute $a) => $a->newInstance(),  (new \ReflectionClass($class))->getAttributes(PageRoute::class));

        return $attribs[0] ?? new PageRoute();
    }
}
