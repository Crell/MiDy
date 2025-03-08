<?php

declare(strict_types=1);

namespace Crell\MiDy\Path;

class AbsolutePath extends Path
{
    protected static function createFromString(string $path)
    {
        $new = new static();
        $new->path = $path;
        $new->segments = array_filter(explode('/', $path));

        $new->ext = pathinfo($new->path, PATHINFO_EXTENSION);

        return $new;
    }

    protected static function createFromSegments(array $segments): static
    {
        $new = new static();

        $new->segments = $segments;

        $new->path = '/' . implode('/', $segments);

        $new->ext = pathinfo($new->path, PATHINFO_EXTENSION);

        return $new;
    }
}
