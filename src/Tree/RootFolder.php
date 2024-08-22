<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Crell\MiDy\TimedCache\FilesystemTimedCache;

class RootFolder extends Folder
{
    public function __construct(
        string $physicalPath,
        FilesystemTimedCache $cache,
    ) {
        parent::__construct($physicalPath, '/', $cache);
    }
}
