<?php

declare(strict_types=1);

namespace Crell\MiDy\Router\EventRouter;

use Crell\MiDy\Router\RequestPath;
use Crell\MiDy\Router\RouteMethodNotAllowed;
use Crell\MiDy\Router\RouteNotFound;
use Crell\MiDy\Router\Router;
use Crell\MiDy\Router\RouteResult;
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
        /** @var RequestPath $requestPath */
        $requestPath = $request->getAttribute(RequestPath::class);
        $candidates = $this->getFilePaths($requestPath);

        if (!$candidates) {
            return new RouteNotFound();
        }

        /** @var RouteResolution $result */
        $event = $this->dispatcher->dispatch(new RouteResolution(
            $request,
            $requestPath,
            $candidates,
            $this->routesPath,
        ));

        return $event->routeResult ?? new RouteMethodNotAllowed([]);
    }

    private function getFilePaths(RequestPath $requestPath): array
    {
        return glob("{$this->routesPath}{$requestPath->normalizedPath}.{$requestPath->ext}");
    }
}
