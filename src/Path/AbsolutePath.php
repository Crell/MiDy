<?php

declare(strict_types=1);

namespace Crell\MiDy\Path;

class AbsolutePath extends Path
{
    public function __construct(string $path)
    {
        $this->segments = array_filter(explode('/', $path));

        $pathinfo = pathinfo($path);
        $this->ext = $pathinfo['extension'] ?? null;
    }

    public function __toString(): string
    {
        return '/' . parent::__toString();
    }
}
