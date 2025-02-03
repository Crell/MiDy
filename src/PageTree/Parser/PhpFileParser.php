<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\ClassFinder;
use Crell\MiDy\PageTree\Attributes\PageRoute;
use Crell\MiDy\PageTree\Model\ParsedFileInformation;
use Crell\MiDy\PageTree\Model\ParsedFrontmatter;
use Crell\MiDy\PageTree\ParsedFile;

class PhpFileParser implements FileParser
{
    public private(set) array $supportedExtensions = ['php'];

    public function __construct(
        private readonly ClassFinder $finder = new ClassFinder(),
    ) {}

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): ParsedFrontmatter
    {
        $physicalPath = $fileInfo->getPathname();

        return $this->extractFrontMatter($physicalPath);
    }

    private function extractFrontMatter(string $physicalPath): PageRoute
    {
        require_once $physicalPath;
        $class = $this->finder->getClass($physicalPath);

        if (!$class) {
            return new PageRoute();
        }

        $attribs = array_map(fn(\ReflectionAttribute $a) => $a->newInstance(),  (new \ReflectionClass($class))->getAttributes(PageRoute::class));

        return $attribs[0] ?? new PageRoute(title: $class);
    }
}
