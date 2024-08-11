<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Webmozart\Glob\Glob;

class FlattenedFileSystemProvider extends GlobFileSystemProvider
{
    protected function getGlobPattern(string $path): string
    {
        $basePath = $path === '/'
            ? $this->rootPath
            : str_replace($path, '', $this->rootPath);

        return $basePath . rtrim($path, '/') . '/**/*.*';
    }

    public function find(string $pattern, Folder $parent): PageList
    {
        // This is to avoid duplicates in the path that appear both in the
        // root for this provider and in the pattern.
        $patternBase = Glob::getBasePath($pattern);
        $newRoot = str_replace($patternBase, '', $this->rootPath);
        $newPattern = $newRoot . $pattern;

        if (!str_ends_with($newPattern, '**')) {
            $newPattern .= '/**/*.*';
        }

        $files = Glob::glob($newPattern);

        return $this->indexFileListByName($files, $parent);
    }
}
