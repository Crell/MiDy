<?php

declare(strict_types=1);

namespace Crell\MiDy\Router\HandlerRouter;

use Crell\MiDy\PageTree\Page;
use Crell\Carica\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

interface PageHandler
{
    public array $supportedMethods { get; }

    public array $supportedExtensions { get; }

    public function handle(ServerRequestInterface $request, Page $page, string $ext): ?RouteResult;
}
