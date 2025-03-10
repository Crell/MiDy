<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\Path\AbsolutePath;
use Crell\MiDy\Path\Path;

class LogicalPath extends AbsolutePath
{
    public static function create(string|self $path): LogicalPath
    {
        // This lets us easily handle "string or LogicalPath" arguments by doing
        // the upcasting transparently here.
        if ($path instanceof self) {
            return $path;
        }
        return self::createFromString($path);
    }

}
