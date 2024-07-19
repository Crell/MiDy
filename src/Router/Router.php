<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

use Crell\MiDy\PageHandlers\HtmlHandler;
use Crell\MiDy\PageHandlers\LatteHandler;
use Psr\Http\Message\ServerRequestInterface;

use function PHPUnit\Framework\isNan;

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

        if (in_array("$file.latte", $files, true)) {
            return new RouteSuccess(
                action:LatteHandler::class,
                method: 'GET',
                vars: [
                    'file' => "$file.latte",
                ],
            );
        }


        return new RouteNotFound();
    }
}
