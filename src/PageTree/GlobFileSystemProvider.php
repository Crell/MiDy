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
     * @return array<string, string>
     *   The logical name of the child, mapped to its absolute file path.
     */
    public function children(string $path, Folder $parent): PageList
    {
        $files = Glob::glob($this->getGlobPattern($path));

        return $this->indexFileListByName($files, $parent);
    }

    protected function indexFileListByName(array $files, Folder $parent): PageList
    {
        $vFiles = [];
        foreach ($files as $file) {
            $pathinfo = \pathinfo($file);
            $name = $pathinfo['basename'];
            $name = empty($pathinfo['extension'])
                ? $name
                : str_replace(".{$pathinfo['extension']}", '', $name);

            $vFiles[$name][$pathinfo['extension'] ?? ''] = $file;
        }

        $ret = [];
        foreach ($vFiles as $name => $paths) {
            // @todo This will fail if there is both a file and folder
            // with the same name.  I don't know how to deal with that.
            $childUrlPath = rtrim($parent->urlPath, '/') . '/' . $name;
            if (array_key_first($paths) === '') {
                // It's a directory.
                $ret[$name] = new Folder($childUrlPath, $parent->findChildProviders($childUrlPath), ucfirst($name));
            } else {
                $ret[$name] = new FilesystemPage($childUrlPath, ucfirst($name), $paths);
            }
        }

        return new PageList($ret);
    }

    /**
     * Returns the glob pattern to use for finding files from the path.
     *
     * @param string $path
     * @return string
     */
    abstract protected function getGlobPattern(string $path): string;

    protected function logicalName(string $path): string
    {
        $pathinfo = \pathinfo($path);

        $name = $pathinfo['basename'];
        return empty($pathinfo['extension'])
            ? $name
            : str_replace(".{$pathinfo['extension']}", '', $name);
    }
}

