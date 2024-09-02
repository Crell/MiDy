<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlers;

use Crell\MiDy\Router\HandlerRouter\PageHandler;
use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\PageTree\Page;
use Psr\Http\Message\ServerRequestInterface;

interface SupportsTrailingPath extends PageHandler
{
    public function handle(ServerRequestInterface $request, Page $page, string $ext, array $trailing = []): ?RouteResult;
}
