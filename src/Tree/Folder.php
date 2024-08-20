<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Traversable;

class Folder implements \Countable, \IteratorAggregate
{
    private array $children = [];

    public function __construct(
        protected readonly string $physicalPath,
        protected readonly string $logicalPath,
        private FileBackedCache $cache,
    ) {}

    public function getIterator(): Traversable
    {
        $this->reindex();

        foreach ($this->children as $logicalPath => $child) {
            if ($child instanceof FolderRef) {
                $child = new Folder($child->physicalPath, $child->logicalPath, $this->cache);
                $this->children[$logicalPath] = $child;
            }
            yield $child;
        }
    }

    public function child(string $name): Page|Folder|null
    {
        $path = ltrim($this->logicalPath, '/') . '/' . $name;
        if (!array_key_exists($path, $this->children)) {
            return null;
        }

        $child = $this->children[$path];
        if ($child instanceof Page) {
            return $child;
        }
        if ($child instanceof FolderRef) {
            $this->children[$path] = new Folder($child->physicalPath, $child->logicalPath, $this->cache);
            return $this->children[$path];
        }
        if ($child instanceof Folder) {
            return $child;
        }
        return null;
    }

    public function count(): int
    {
        $this->reindex();
        return count($this->children);
    }

    public function reindex(): void
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

        foreach ($toBuild as $logicalPath => $child) {
            if ($child['type'] === 'folder') {
                $this->children[$logicalPath] = new FolderRef($child['physicalPath'], $logicalPath);
            } else {
                $this->children[$logicalPath] = new Page($logicalPath, $child['variants']);
            }
        }
    }
}

/*
 Every folder gets its own cache entry.
The folder's child pages are embedded in it.
The folder's child directories are represented as stubs, that point to another folder entry.
That means every folder still needs access to the cache front-end to look up the folders lazily.
This does mean lookups need to go through every folder... Hm.  Future optimization.
 */