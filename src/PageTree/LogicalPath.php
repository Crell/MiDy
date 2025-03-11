<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\Path\AbsolutePath;

class LogicalPath extends AbsolutePath
{
    public private(set) string $withoutExtension {
        get => $this->withoutExtension ??= $this->derivePathWithoutExtension();
    }

    public static function create(string|self $path): LogicalPath
    {
        // This lets us easily handle "string or LogicalPath" arguments by doing
        // the upcasting transparently here.
        if ($path instanceof self) {
            return $path;
        }
        return self::createFromString($path);
    }

    protected function derivePathWithoutExtension(): string
    {
        if (!$this->ext) {
            return $this->path;
        }

        return substr($this->path, 0, strpos($this->path, '.'));
    }
}
