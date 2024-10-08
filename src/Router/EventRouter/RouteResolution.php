<?php

declare(strict_types=1);

namespace Crell\MiDy\Router\EventRouter;

use Crell\MiDy\Router\RequestPath;
use Crell\MiDy\Router\RouteResult;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteResolution implements StoppableEventInterface
{
    public readonly RouteResult $routeResult;

    public function __construct(
        public readonly ServerRequestInterface $request,
        public readonly RequestPath $requestPath,
        public readonly array $candidates,
        public readonly string $routesPath,
    ) {}

    public function routingResult(RouteResult $result): void
    {
        $this->routeResult = $result;
    }

    public function isPropagationStopped(): bool
    {
        return isset($this->routeResult);
    }
}
