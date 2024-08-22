<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

class RootFolder extends FolderWrapper
{
    public function __construct(
        string $physicalPath,
        PathCache $cache,
    ) {
        parent::__construct($physicalPath, '/', $cache);
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
}
