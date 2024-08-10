<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

readonly class ProviderMap extends PathMap
{
    public function findForPath(string $path): RouteProvider
    {
        // This is just to change the return type.  Oh for proper generics...
        return parent::findForPath($path);
    }
}