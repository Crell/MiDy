<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class EventRouter implements Router
{
    public function __construct(
        private string $routesPath,
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function route(ServerRequestInterface $request): RouteResult
    {
        [$requestPath, $ext] = $this->getRequestPath($request);
        $candidates = $this->getFilePaths($requestPath, $ext);

        if (!$candidates) {
            return new RouteNotFound();
        }

        /** @var RouteResolution $result */
        $event = $this->dispatcher->dispatch(new RouteResolution(
            $request,
            $requestPath,
            $candidates,
        ));

        return $event->routeResult ?? new RouteMethodNotAllowed([]);
    }

    private function getRequestPath(ServerRequestInterface $request): array
    {
        $path = $request->getUri()->getPath();

        if ($path === '/') {
            $path = '/home';
        }

        if (str_contains($path, '.')) {
            [$path, $ext] = \explode('.', $path);
        } else {
            $ext = '*';
        }

        return [$this->routesPath . $path, $ext];
    }

    private function getFilePaths(string $requestPath, string $ext = '*'): array
    {
        return glob("$requestPath.$ext");
    }
}
