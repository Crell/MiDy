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
}
