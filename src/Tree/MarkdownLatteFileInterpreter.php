<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;

class MarkdownLatteFileInterpreter implements FileInterpreter
{
    public function __construct(
        private MarkdownPageLoader $loader,
    ) {}

    public function supportedExtensions(): array
    {
        return ['md'];
    }

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): RouteFile|FileInterpreterError
    {
        $page = $this->loader->load($fileInfo->getPathname());

        $slug = $page->slug ?? $basename;

        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . $slug;

        return new RouteFile(
            physicalPath: $fileInfo->getPathname(),
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            mtime: $fileInfo->getMTime(),
            title: $page->title ?? ucfirst($basename),
        );
    }
}
