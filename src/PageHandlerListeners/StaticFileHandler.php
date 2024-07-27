<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlerListeners;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\Router\RouteResolution;
use Crell\MiDy\Router\RouteSuccess;
use Crell\MiDy\Services\ResponseBuilder;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

readonly class StaticFileHandler
{
    public function __construct(
        private ResponseBuilder $builder,
        private StreamFactoryInterface $streamFactory,
        private StaticRoutes $config,
    ) {}

    public function __invoke(RouteResolution $event): void
    {
        if ($event->request->getMethod() !== 'GET') {
            return;
        }

        foreach ($this->config->allowedExtensions as $ext => $contentType) {
            if (in_array("{$event->routesPath}{$event->requestPath->normalizedPath}.$ext", $event->candidates, true)) {
                $event->routingResult(
                    new RouteSuccess(
                        action: $this->action(...),
                        method: 'GET',
                        vars: [
                            'file' => "{$event->routesPath}{$event->requestPath->normalizedPath}.$ext",
                            'contentType' => $contentType,
                        ],
                    )
                );
            }
        }
    }

    public function action(ServerRequestInterface $request, string $file, string $contentType): ResponseInterface
    {
        $stream = $this->streamFactory->createStreamFromFile($file);
        $stream->rewind();

        return $this->builder->ok($stream, $contentType);
    }
}
