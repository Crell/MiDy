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

        $attrib = $this->frontmatter($physicalPath);
        $slug = $attrib?->slug ?? $basename;

        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . $slug;

        return new RouteFile(
            physicalPath: $physicalPath,
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            mtime: $fileInfo->getMTime(),
            title: $attrib?->title ?? ucfirst($basename),
        );
    }

    private function frontmatter(string $physicalPath): ?PageRoute
    {
        require_once $physicalPath;
        $class = $this->finder->getClass($physicalPath);

        if (!$class) {
            return null;
        }

        $attribs = array_map(fn(\ReflectionAttribute $a) => $a->newInstance(),  (new \ReflectionClass($class))->getAttributes(PageRoute::class));

        return $attribs[0] ?? null;
    }
}
