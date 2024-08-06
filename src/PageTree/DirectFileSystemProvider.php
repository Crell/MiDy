<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Webmozart\Glob\Glob;

class DirectFileSystemProvider implements RouteProvider
{
    /**
     * @param string $rootPath
     *   The absolute file path on disk where the whole tree is rooted.
     *   It MUST NOT include a trailing slash.
     */
    public function __construct(
        private string $rootPath,
    ) {}

    /**
     * @param string $path
     *   The relative path.
     * @return iterable<string, string>
     *   The logical name of the child, mapped to its absolute file path.
     */
    public function children(string $path): iterable
    {
        $pattern = $this->rootPath . rtrim($path, '/') . '/*';

        $files = Glob::glob($pattern);

        $ret = [];
        foreach ($files as $file) {
            $ret[$this->logicalName($file)] = $file;
        }
        return $ret;
    }

    private function logicalName(string $path): string
    {
        $name = pathinfo($path, PATHINFO_BASENAME);
        $dot = strrpos($name, '.');
        if ($dot) {
            $name = substr($name, 0, $dot);
        }
        return $name;
    }
}

