<?php

declare(strict_types=1);

namespace Crell\MiDy\Middleware;

use Crell\MiDy\Errors\MethodNotAllowed;
use Crell\MiDy\Errors\NotFound;
use Crell\MiDy\Events\HandleError;
use Crell\MiDy\Router\Router;
use Crell\MiDy\Services\ResponseBuilder;
use Crell\MiDy\Router\RouteMethodNotAllowed;
use Crell\MiDy\Router\RouteNotFound;
use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\Router\RouteSuccess;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class RoutingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Router $router,
        private ResponseBuilder $responseBuilder,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->router->route($request);

        if ($result instanceof RouteSuccess) {
            $request = $request->withAttribute(RouteResult::class, $result);
            return $handler->handle($request);
        }
        if ($result instanceof RouteNotFound) {
            /** @var HandleError $event */
            $event = $this->eventDispatcher->dispatch(new HandleError(new NotFound($request, $result), $request));
            return $event->getResponse() ?? $this->responseBuilder->notFound('Not Found', 'text/plain');
        }
        if ($result instanceof RouteMethodNotAllowed) {
            /** @var HandleError $event */
            $event = $this->eventDispatcher->dispatch(new HandleError(new MethodNotAllowed($request, $result->allowedMethods), $request));
            return $event->getResponse() ?? $this->responseBuilder->createResponse(405, 'Not Found', 'text/plain');
        }

        // It should be impossible to get here, for type reasons.
        return $this->responseBuilder->createResponse(500, 'How did that happen?', 'text/plain');
    }
}
