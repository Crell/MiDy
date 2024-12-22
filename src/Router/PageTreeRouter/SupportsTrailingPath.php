<?php

declare(strict_types=1);

namespace Crell\MiDy\Router\PageTreeRouter;

use Crell\MiDy\PageTreeDB2\Page;
use Crell\MiDy\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

interface SupportsTrailingPath extends PageHandler
{
    public function handle(ServerRequestInterface $request, Page $page, string $ext, array $trailing = []): ?RouteResult;
}
