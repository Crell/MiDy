<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

interface RouteProvider
{
    public function children(string $path): array;
}