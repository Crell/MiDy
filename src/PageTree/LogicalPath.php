<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\Path\AbsolutePath;

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
        $new = self::createFromString($path);
        if ($new->stream) {
            throw new \InvalidArgumentException('A logical path may not make use of streams.');
        }
        return $new;
    }

    public static function fromPhysicalPath(PhysicalPath $physicalPath): LogicalPath
    {
        // This retains the path portion, but strips off the stream and extension, if any.
        $segments = $physicalPath->segments;
        if (str_contains($physicalPath->end, '.')) {
            $end = array_pop($segments);
            $segments[] = substr($end, 0, strpos($end, '.'));
            $segments = array_values($segments);
        }
        return self::createFromSegments($segments);
    }

    protected function derivePathWithoutExtension(): string
    {
        if (!$this->ext) {
            return $this->path;
        }

        return substr($this->path, 0, strpos($this->path, '.'));
    }
}
