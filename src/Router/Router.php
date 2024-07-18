<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

use Crell\MiDy\Documents\Product;
use Crell\MiDy\Services\Actions\ProductCreate;
use Crell\MiDy\Services\Actions\ProductGet;
use Crell\MiDy\Services\Actions\StaticPath;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class Router
{
    private array $routes = [];

    public function __construct()
    {

    }

    public function route(RequestInterface $request): RouteResult
    {
        $routeSet = $this->routes[$request->getUri()->getPath()] ?? null;

        if (is_null($routeSet)) {
            return new RouteNotFound();
        }

        $result = $routeSet[strtolower($request->getMethod())] ?? null;

        if (is_null($result)) {
            return new RouteMethodNotAllowed(['GET', 'POST']);
        }

        return $result;
    }
}
