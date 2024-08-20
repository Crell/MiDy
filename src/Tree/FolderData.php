<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Traversable;

class FolderData implements Folder
{
    public function __construct(
        protected readonly string $physicalPath,
        protected readonly string $logicalPath,
        protected readonly array $children,
    ) {}

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->children);
    }

    public function child(string $name): Page|FolderData|null
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
            $this->children[$path] = new FolderData($child->physicalPath, $child->logicalPath, $this->cache);
            return $this->children[$path];
        }
        if ($child instanceof FolderData) {
            return $child;
        }
        return null;
    }

    public function count(): int
    {
        return count($this->children);
    }


}

/*
 Every folder gets its own cache entry.
The folder's child pages are embedded in it.
The folder's child directories are represented as stubs, that point to another folder entry.
That means every folder still needs access to the cache front-end to look up the folders lazily.
This does mean lookups need to go through every folder... Hm.  Future optimization.

FolderRef holds the

 */