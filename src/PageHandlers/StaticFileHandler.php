<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlers;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTree\Page;
use Crell\MiDy\Router\HandlerRouter\PageHandler;
use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\Router\RouteSuccess;
use Crell\MiDy\Services\ResponseBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class StaticFileHandler implements PageHandler
{
    public private(set) array $supportedMethods = ['GET'];
    public array $supportedExtensions {
        get => array_keys($this->config->allowedExtensions);
    }

    public function __construct(
        private readonly ResponseBuilder $builder,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly StaticRoutes $config,
    ) {}

    public function handle(ServerRequestInterface $request, Page $page, string $ext): ?RouteResult
    {
        $contentType = $this->config->allowedExtensions[$ext];

        return new RouteSuccess(
            action: $this->action(...),
            method: $request->getMethod(),
            vars: [
                'file' => $page->variant($ext)->physicalPath,
                'contentType' => $contentType,
            ],
        );
    }

    public function action(string $file, string $contentType): ResponseInterface
    {
        $stream = $this->streamFactory->createStreamFromFile($file);
        $stream->rewind();

        return $this->builder->ok($stream, $contentType);
    }
}
