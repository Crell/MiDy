<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

class RootFolder extends Folder
{
    public function __construct(
        string $physicalPath,
        FolderParser $parser,
    ) {
        parent::__construct($physicalPath, '/', $parser);
    }

    public function route(string $path): Page|Folder|null
    {
        $dirParts = array_filter(explode('/', $path));

        $child = $this;

        foreach ($dirParts as $pathSegment) {
            $next = $child?->child($pathSegment);
            if ($next instanceof Page) {
                return $next;
            }

            $child = $next;
        }

        return $child;
    }
}
