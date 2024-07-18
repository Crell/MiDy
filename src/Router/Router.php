<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

use Crell\MiDy\PageHandlers\HtmlHandler;
use Psr\Http\Message\ServerRequestInterface;

class Router
{
    private array $routes = [];

    public function __construct(private string $routesPath) {}

    public function route(ServerRequestInterface $request): RouteResult
    {
        return match ($request->getMethod()) {
            'GET' => $this->get($request),
            default => new RouteMethodNotAllowed(['GET', 'POST']),
        };
    }

    private function get(ServerRequestInterface $request): RouteResult
    {
        $path = $request->getUri()->getPath();

        if ($path === '/') {
            $path = '/home';
        }

        $file = $this->routesPath . $path;

        $files = glob("$file.*");

        if (in_array("$file.html", $files, true)) {
            return new RouteSuccess(
                action:HtmlHandler::class,
                method: 'GET',
                vars: [
                    'file' => "$file.html",
                ],
            );
        }

        return new RouteNotFound();
    }
}
