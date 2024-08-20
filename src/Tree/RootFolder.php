<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

class RootFolder extends Folder
{
    public function __construct(
        string $physicalPath,
        FileBackedCache $cache,
    ) {
        parent::__construct($physicalPath, '/', $cache);
    }
}
