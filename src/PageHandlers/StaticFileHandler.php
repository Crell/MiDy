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
        private array $allowedExtensions = [
            'html' => 'text/html',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'svg' => 'image/svg',
            'jpg' => 'image/jpg',
            'webm' => 'image/webm',
        ],
    ) {}

    public function __invoke(RouteResolution $event): void
    {
        if ($event->request->getMethod() !== 'GET') {
            return;
        }

        foreach ($this->allowedExtensions as $ext => $contentType) {
            if (in_array("{$event->path}.$ext", $event->candidates, true)) {
                $event->routingResult(
                    new RouteSuccess(
                        action: $this->action(...),
                        method: 'GET',
                        vars: [
                            'file' => "{$event->path}.$ext",
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
