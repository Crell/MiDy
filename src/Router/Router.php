<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

use Psr\Http\Message\ServerRequestInterface;

interface Router
{
    public function route(ServerRequestInterface $request): RouteResult;
}