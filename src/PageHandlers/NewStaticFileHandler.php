<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlers;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\Router\PageHandler;
use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\Router\RouteSuccess;
use Crell\MiDy\Services\ResponseBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

readonly class NewStaticFileHandler implements PageHandler
{
    public function __construct(
        private ResponseBuilder $builder,
        private StreamFactoryInterface $streamFactory,
        private StaticRoutes $config,
    ) {}

    public function supportedMethods(): array
    {
        return ['GET'];
    }

    public function supportedExtensions(): array
    {
        return array_keys($this->config->allowedExtensions);
    }

    public function handle(ServerRequestInterface $request, string $file, string $ext): ?RouteResult
    {
        $contentType = $this->config->allowedExtensions[$ext];

        return new RouteSuccess(
            action: $this->action(...),
            method: $request->getMethod(),
            vars: [
                'file' => $file,
                'contentType' => $contentType,
            ],
        );
    }

    public function action(ServerRequestInterface $request, string $file, string $contentType): ResponseInterface
    {
        $stream = $this->streamFactory->createStreamFromFile($file);
        $stream->rewind();

        return $this->builder->ok($stream, $contentType);
    }
}
