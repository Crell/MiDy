<?php

declare(strict_types=1);

namespace Crell\MiDy\Middleware;

use Crell\MiDy\Documents\User;
use Crell\MiDy\Errors\PermissionDenied;
use Crell\MiDy\Events\Events\HandleError;
use Crell\MiDy\Services\Authorization\UserAuthorizer;
use Crell\MiDy\Services\ResponseBuilder;
use Crell\MiDy\Services\Router\RequestFormat;
use Crell\MiDy\Services\Router\RouteResult;
use Crell\MiDy\Services\Router\RouteSuccess;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class AuthorizationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private UserAuthorizer $authorizer,
        private ResponseBuilder $responseBuilder,
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var RouteSuccess $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);

        /** @var User $user */
        $user = $request->getAttribute(User::class);

        $permission = $routeResult?->permission;

        // If there's no permission requirement, fail open. Bad, but meh.
        if (!$permission) {
            return $handler->handle($request);
        }

        // Success just goes through the stack.
        if ($this->authorizer->userMay($user, $permission)) {
            return $handler->handle($request);
        }

        // 403 error
        /** @var RequestFormat $format */
        $format = $request->getAttribute(RequestFormat::class);

        // To make this extensible to arbitrary formats, we need a registration
        // mechanism here.  We don't have an error hook/event/pipe to use.
        // For simplicity, just piggy-back on the HandleError event from EventKernel.

        /** @var HandleError $event */
        $event = $this->dispatcher->dispatch(new HandleError(new PermissionDenied($request, $user, $permission), $request));

        return $event->getResponse() ?? $this->responseBuilder->forbidden('Forbidden', 'text/plain');
    }
}
