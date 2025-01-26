<?php

declare(strict_types=1);

namespace Crell\MiDy\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class CacheHeaderMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $isCacheableMethod = in_array(strtoupper($request->getMethod()), ['GET', 'HEAD']);
        $isCacheableStatus = in_array($response->getStatusCode() - ($response->getStatusCode() % 100), [200, 300]);

        // Disable any cache headers that were set on non-cacheable responses.
        if (!$isCacheableMethod || !$isCacheableStatus) {
            $response
                ->withoutHeader('cache-control')
                ->withoutHeader('ETag')
                ->withoutHeader('expires')
            ;
        }

        return $response;
    }
}
