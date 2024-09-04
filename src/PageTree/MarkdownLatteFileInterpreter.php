<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\MarkdownDeserializer\MarkdownError;
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

        if ($page === MarkdownError::FileNotFound) {
            return FileInterpreterError::FileNotSupported;
        }

        $frontmatter = $page->toFrontMatter();

        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . ($frontmatter->slug() ?? $basename);

        return new RouteFile(
            physicalPath: $fileInfo->getPathname(),
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            mtime: $fileInfo->getMTime(),
            frontmatter: $frontmatter,
        );
    }
}
