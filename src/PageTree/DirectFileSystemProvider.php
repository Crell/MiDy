<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Webmozart\Glob\Glob;

class DirectFileSystemProvider extends GlobFileSystemProvider
{
    protected function getGlobPattern(string $path): string
    {
        return $this->rootPath . rtrim($path, '/') . '/*';
    }

    public function find(string $pattern): array
    {
        $globPattern = $this->rootPath . $pattern;

        $files = Glob::glob($globPattern);

        return $this->indexFileListByName($files);
    }
}
