<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;

class EventRouter implements Router
{
    public function __construct(
        private string $routesPath,
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function route(ServerRequestInterface $request): RouteResult
    {
        $requestPath = $this->getRequestPath($request);
        $candidates = $this->getFilePaths($requestPath);

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

    private function getRequestPath(ServerRequestInterface $request): string
    {
        $path = $request->getUri()->getPath();

        if ($path === '/') {
            $path = '/home';
        }

        return $this->routesPath . $path;
    }

    private function getFilePaths(string $requestPath): array
    {
        return glob("$requestPath.*");
    }
}
