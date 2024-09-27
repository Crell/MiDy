<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\FileInterpreter;

use Crell\MiDy\MarkdownDeserializer\MarkdownError;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\PageTree\PageFile;

class MarkdownLatteFileInterpreter implements FileInterpreter
{
    public function __construct(
        private MarkdownPageLoader $loader,
    ) {}

    public function supportedExtensions(): array
    {
        return ['md'];
    }

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath, string $basename): PageFile|FileInterpreterError
    {
        $page = $this->loader->load($fileInfo->getPathname());

        if ($page === MarkdownError::FileNotFound) {
            return FileInterpreterError::FileNotSupported;
        }

        $frontmatter = $page->pageInformation();

        $logicalPath = rtrim($parentLogicalPath, '/') . '/' . ($frontmatter->slug() ?? $basename);

        return new PageFile(
            physicalPath: $fileInfo->getPathname(),
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            mtime: $fileInfo->getMTime(),
            info: $frontmatter,
        );
    }
}
