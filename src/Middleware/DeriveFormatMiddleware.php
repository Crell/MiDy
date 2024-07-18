<?php

declare(strict_types=1);

namespace Crell\MiDy\Middleware;

use Crell\MiDy\Services\FormatDeriver;
use Crell\MiDy\Router\RequestFormat;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class DeriveFormatMiddleware implements MiddlewareInterface
{
    public function __construct(
        private FormatDeriver $deriver,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentFormat = $this->deriver->mapType($request->getHeader('content-type')[0] ?? '');

        $acceptFormat = $this->deriver->mapType($request->getHeader('accept')[0] ?? '');

        // If there is no Accept header but there is a content type, assume the agent
        // wants the same type back.
        if ($acceptFormat === 'unknown' && $contentFormat !== 'unknown') {
            $acceptFormat = $contentFormat;
        }

        $request = $request->withAttribute(RequestFormat::class, new RequestFormat(
            accept: $acceptFormat,
            content: $contentFormat,
        ));

        return $handler->handle($request);
    }
}
