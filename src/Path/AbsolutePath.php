<?php

declare(strict_types=1);

namespace Crell\MiDy\Path;

class AbsolutePath extends Path
{
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->segments = array_filter(explode('/', $path));

        $pathinfo = pathinfo($path);
        $this->ext = $pathinfo['extension'] ?? null;
    }
}
