<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\PageTree\FolderParser\FolderParser;

class RootFolder extends Folder
{
    public function __construct(
        string $physicalPath,
        FolderParser $parser,
    ) {
        parent::__construct($physicalPath, '/', $parser);
    }

    public function route(string $path): ?Page
    {
        $dirParts = array_filter(explode('/', $path));

        $child = $this;

        foreach ($dirParts as $pathSegment) {
            $next = $child?->get($pathSegment);
            if (! $next instanceof Folder) {
                return $next;
            }

            $child = $next;
        }

        return $child;
    }
}
