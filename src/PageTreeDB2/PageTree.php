<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

class PageTree
{
    /**
     * @var array<string, string>
     *     A map from logical folder paths to the physical paths they correspond to.
     */
    private array $mountPoints = [];

    public function __construct(
        private PageCacheDB $cache,
        private Parser $parser,
        string $rootPhysicalPath,
    ) {
        $this->mount($rootPhysicalPath, '/');
    }

    public function mount(string $physicalPath, string $logicalPath): void
    {
        $this->mountPoints[$logicalPath] = $physicalPath;
    }

    /**
     * Returns the Folder read-object for this path.
     */
    public function folder(string $logicalPath): Folder
    {
        return new Folder($this->loadFolder($logicalPath), $this);
    }

    /**
     * Retrieves all visible pages under the specified path.
     *
     * @return array<string, Page>
     */
    public function pages(string $folderPath): array
    {
        $files = $this->cache->readFiles($folderPath);

        foreach ($files as $file) {
            if (filemtime($file->physicalPath) > $file->mtime) {
                // Need to rescan this file.
                $file = $this->parser->parseFile(new \SplFileInfo($file->physicalPath), $file->folder);
            }
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

    public function reindexAll(string $logicalRoot = '/'): void
    {
        $this->reindexFolder($logicalRoot);

        foreach ($this->cache->childFolders($logicalRoot) as $child) {
            $this->reindexAll($child->logicalPath);
        }
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
        // If it's one of the mount roots, just parse that directly.
        if (array_key_exists($logicalPath, $this->mountPoints)) {
            $this->parser->parseFolder($this->mountPoints[$logicalPath], $logicalPath, $this->mountPoints);
            // In case there is another mount point that is an immediate child,
            // reindex that too so we get any index file in it.
            foreach ($this->mountPoints as $logicalMount => $physicalMount) {
                if ($logicalMount !== $logicalPath && dirname($logicalMount) === $logicalPath) {
                    $this->reindexFolder($logicalMount);
                }
            }
            return $this->cache->readFolder($logicalPath);
        }

        // Otherwise, we need to get the logical parent folder and get its physical
        // path, so we know what to parse.  If the parent is not yet indexed,
        // it will get reindexed, too.
        $parts = explode('/', $logicalPath);
        $slug = array_pop($parts);
        $parentFolderPath = '/' . implode('/', array_filter($parts));
        $parent = $this->loadFolder($parentFolderPath);
        $this->parser->parseFolder($parent->physicalPath . '/' . $slug, $logicalPath, $this->mountPoints);
        return $this->cache->readFolder($logicalPath);
    }
}