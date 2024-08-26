<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Crell\MiDy\TimedCache\FilesystemTimedCache;

class RootFolder extends Folder
{
    public function __construct(
        string $physicalPath,
        FilesystemTimedCache $cache,
        FileInterpreter $interpreter,
    ) {
        parent::__construct($physicalPath, '/', $cache, $interpreter);
    }
}
