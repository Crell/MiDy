<?php

declare(strict_types=1);

namespace Crell\MiDy\Path;

class PathFragment extends Path
{
    public function __construct(string $path)
    {
        $pathinfo = pathinfo($path);
        $this->ext = $pathinfo['extension'] ?? null;

        $this->segments = explode('/', $path);
    }
}
