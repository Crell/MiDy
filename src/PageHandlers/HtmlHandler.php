<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlers;

use Crell\MiDy\Router\RouteResolution;
use Crell\MiDy\Router\RouteSuccess;
use Crell\MiDy\Services\ResponseBuilder;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

readonly class HtmlHandler
{
    public function __construct(
        private ResponseBuilder $builder,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function __invoke(RouteResolution $event): void
    {
        if ($event->request->getMethod() !== 'GET') {
            return;
        }

        if (in_array("{$event->path}.html", $event->candidates, true)) {
            $event->routingResult(
                new RouteSuccess(
                    action: $this->action(...),
                    method: 'GET',
                    vars: [
                        'file' => "{$event->path}.html",
                    ],
                )
            );
        }
    }

    public function action(ServerRequestInterface $request, string $file): ResponseInterface
    {
        $stream = $this->streamFactory->createStreamFromFile($file);
        $stream->rewind();

        return $this->builder->ok($stream);
    }
}
