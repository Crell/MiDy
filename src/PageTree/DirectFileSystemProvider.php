<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class DirectFileSystemProvider extends GlobFileSystemProvider
{
    protected function getGlobPattern(string $path): string
    {
        return $this->rootPath . rtrim($path, '/') . '/*';
    }
}

