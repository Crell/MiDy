<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Traversable;

class FolderData implements Folder
{
    public function __construct(
        protected readonly string $physicalPath,
        protected readonly string $logicalPath,
        public readonly array $children,
    ) {}

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->children);
    }

    public function child(string $name): Page|FolderData|null
    {
        var_dump($this->children);
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