<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

interface RouteProvider
{
    public function children(string $path, Folder $parent): PageList;

    public function find(string $pattern, Folder $parent): PageList;
}
