<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

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
    public function folder(string $logicalPath): ParsedFolder
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
        $parent = $this->folder(implode('/', $parts));
        $this->parser->parseFolder($parent->physicalPath . '/' . $slug, $logicalPath);
        return $this->cache->readFolder($logicalPath);
    }
}
