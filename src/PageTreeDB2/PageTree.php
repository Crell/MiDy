<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use function Crell\fp\prop;

class PageTree
{
    /**
     * @var array<string, string>
     *     A map from logical folder paths to the physical paths they correspond to.
     */
    private array $paths = [];

    public function __construct(
        private PageCacheDB $cache,
        private Parser $parser,
        string $rootPhysicalPath,
    ) {
        $this->mount($rootPhysicalPath, '/');
    }

    public function mount(string $physicalPath, string $logicalPath): void
    {
        $this->paths[$logicalPath] = $physicalPath;
    }

    /**
     * Returns the Folder read-object for this path.
     */
    public function folder(string $logicalPath): Folder
    {
        return new Folder($this->loadFolder($logicalPath), $this);
    }

    public function pages(string $folderPath): array
    {
        $files = $this->cache->readFiles($folderPath);

        foreach ($files as $file) {
            $grouped[$file->pathName][] = $file;
        }

        $pages = [];
        foreach ($grouped as $logicalPath => $set) {
            $pages[$logicalPath] = match(count($set)) {
                1 => new PageFile($set[0]),
                default => new AggregatePage($logicalPath, $set),
            };
        }

        return $pages;
    }

    private function loadFolder(string $logicalPath): ParsedFolder
    {
        $folder = $this->cache->readFolder($logicalPath);

        if (!$folder || $folder->mtime < filemtime($folder->physicalPath)) {
            $folder = $this->reindexFolder($logicalPath);
        }

        return $folder;
    }

    /**
     * Re-parse a folder at a given location by reparsing its parent's contents.
     *
     * @param string $logicalPath
     * @return ParsedFolder
     */
    private function reindexFolder(string $logicalPath): ParsedFolder
    {
        if (array_key_exists($logicalPath, $this->paths)) {
            $this->parser->parseFolder($this->paths[$logicalPath], $logicalPath);
        }
        $parts = explode('/', $logicalPath);
        $slug = array_pop($parts);
        $parent = $this->loadFolder('/' . implode('/', $parts));
        $this->parser->parseFolder($parent->physicalPath . '/' . $slug, $logicalPath);
        return $this->cache->readFolder($logicalPath);
    }
}
