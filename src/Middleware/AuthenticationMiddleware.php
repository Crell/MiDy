<?php

declare(strict_types=1);

namespace Crell\MiDyMiDy\Middleware;

use Crell\MiDy\Documents\User;
use Crell\MiDy\Services\Authentication\UserAuthenticator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private UserAuthenticator $authenticator,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->authenticator->authenticate($request);

        return $handler->handle($request->withAttribute(User::class, $user));
    }

}
