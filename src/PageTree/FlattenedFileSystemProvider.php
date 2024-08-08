<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class FlattenedFileSystemProvider extends GlobFileSystemProvider
{
    protected function getGlobPattern(string $path): string
    {
        $basePath = $path === '/'
            ? $this->rootPath
            : str_replace($path, '', $this->rootPath);

        return $basePath . rtrim($path, '/') . '/**/*.*';
    }
}

