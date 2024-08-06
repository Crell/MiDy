<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Webmozart\Glob\Glob;

abstract class GlobFileSystemProvider implements RouteProvider
{
    /**
     * @param string $rootPath
     *   The absolute file path on disk where the whole tree is rooted.
     *   It MUST NOT include a trailing slash.
     */
    public function __construct(
        protected readonly string $rootPath,
    ) {}

    /**
     * @param string $path
     *   The relative path.
     * @return iterable<string, string>
     *   The logical name of the child, mapped to its absolute file path.
     */
    public function children(string $path): iterable
    {
        $files = Glob::glob($this->getGlobPattern($path));

        $ret = [];
        foreach ($files as $file) {
            $ret[$this->logicalName($file)] = $file;
        }
        return $ret;
    }

    /**
     * Returns the glob pattern to use for finding files from the path.
     *
     * @param string $path
     * @return string
     */
    abstract protected function getGlobPattern(string $path): string;

    private function logicalName(string $path): string
    {
        $pathinfo = \pathinfo($path);

        $name = $pathinfo['basename'];
        return empty($pathinfo['extension'])
            ? $name
            : str_replace(".{$pathinfo['extension']}", '', $name);
    }
}

