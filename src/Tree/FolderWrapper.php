<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Traversable;

class FolderWrapper implements Folder
{
    private FolderData $folder;

    public function __construct(
        public readonly string $physicalPath,
        public readonly string $logicalPath,
        protected readonly PathCache $cache,
    ) {}

    public function count(): int
    {
        return $this->getFolder()->count();
    }

    public function getIterator(): Traversable
    {
        /** @var FolderRef|Page $child */
        foreach ($this->getFolder()->children as $child) {
            if ($child instanceof FolderRef) {
                yield new FolderWrapper($child->physicalPath, $child->logicalPath, $this->cache);
            } else {
                yield $child;
            }
        }
    }

    public function find(string $path): Page|Folder|null
    {
        $dirParts = array_filter(explode('/', $path));

        $child = $this;

        foreach ($dirParts as $pathSegment) {
            $child = $child?->child($pathSegment);
        }

        return $child;
    }

    public function children(): Traversable
    {
        return $this;
    }

    public function child(string $name): Folder|Page|null
    {
        $pathinfo = pathinfo($name);

        $child = $this->getFolder()->children[$pathinfo['filename']] ?? null;

        if ($child instanceof FolderRef) {
            return new FolderWrapper($child->physicalPath, $child->logicalPath, $this->cache);
        }
        if (isset($pathinfo['extension'])) {
            /** @var Page $child */
            $child = $child->limitTo($pathinfo['extension']);
        }

        return $child;
    }

    // @todo Make this better.
    public function title(): string
    {
        return ucfirst(pathinfo($this->logicalPath, PATHINFO_BASENAME));
    }

    public function path(): string
    {
        return $this->logicalPath;
    }

    protected function getFolder(): FolderData
    {
        return $this->folder ??= $this->cache->getIfOlderThan($this->logicalPath, filemtime($this->physicalPath), $this->reindex(...));
    }

    public function reindex(): FolderData
    {
        $iter = new \FilesystemIterator($this->physicalPath);

        $toBuild = [];

        /** @var \SplFileInfo $file */
        foreach ($iter as $file) {
            if ($file->isFile()) {
                $physicalPath = $file->getPathname();
                $pathinfo = pathinfo($physicalPath);
                // @todo This gets more flexible.
                $logicalPath = ltrim($this->logicalPath, '/') . '/' . $pathinfo['filename'];

                $toBuild[$logicalPath] ??= [
                    'type' => 'page',
                    'variants' => [],
                ];

                $toBuild[$logicalPath]['variants'][$file->getExtension()] = $file;

            } else {
                $physicalPath = $file->getPathname();
                // @todo This gets more flexible.
                $logicalPath = ltrim($this->logicalPath, '/') . '/' . $file->getFilename();

                $toBuild[$logicalPath] ??= [
                    'type' => 'folder',
                    'physicalPath' => $physicalPath,
                    'data' => '',
                ];

                $toBuild[$logicalPath]['data'] = $file;
            }
        }

        $children = [];
        foreach ($toBuild as $logicalPath => $child) {
            $fileName = pathinfo($logicalPath, PATHINFO_FILENAME);
            if ($child['type'] === 'folder') {
                $children[$fileName] = new FolderRef($child['physicalPath'], $logicalPath);
            } else {
                $children[$fileName] = new Page($logicalPath, $child['variants']);
            }
        }

        return new FolderData($this->physicalPath, $this->logicalPath, $children);
    }


}
