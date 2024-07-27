<?php

declare(strict_types=1);

namespace Crell\MiDy\Middleware;

use Crell\MiDy\Router\RequestPath;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class RequestPathMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        // @todo Make this configurable.
        if ($path === '/') {
            $path = '/home';
        }

        $requestPath = new RequestPath($path);

        return $handler->handle($request->withAttribute(RequestPath::class, $requestPath));
    }
}
