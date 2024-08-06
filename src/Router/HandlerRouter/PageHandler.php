<?php

declare(strict_types=1);

namespace Crell\MiDy\Router\HandlerRouter;

use Crell\MiDy\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

interface PageHandler
{
    public function supportedMethods(): array;

    public function supportedExtensions(): array;

    public function handle(ServerRequestInterface $request, string $file, string $ext): ?RouteResult;
}
