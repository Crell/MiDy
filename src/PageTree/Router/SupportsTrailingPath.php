<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Router;

use Crell\MiDy\PageTree\Page;
use Crell\MiDy\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

interface SupportsTrailingPath extends PageHandler
{
    public function handle(ServerRequestInterface $request, Page $page, string $ext, array $trailing = []): ?RouteResult;
}
