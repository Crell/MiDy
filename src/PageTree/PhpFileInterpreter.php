<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\ClassFinder;
use Crell\MiDy\PageTree\Attributes\PageRoute;

readonly class PhpFileInterpreter implements FileInterpreter
{
    public function __construct(
        private ClassFinder $finder,
    ) {}

    public function supportedExtensions(): array
    {
        return ['php'];
    }

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): RouteFile|FileInterpreterError
    {
        $physicalPath = $fileInfo->getPathname();

        $frontmatter = $this->extractFrontMatter($physicalPath);

        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . ($frontmatter->slug() ?? $basename);

        return new RouteFile(
            physicalPath: $physicalPath,
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            mtime: $fileInfo->getMTime(),
            frontmatter: $frontmatter,
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
