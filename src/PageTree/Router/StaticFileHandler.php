<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Router;

use Crell\Carica\ResponseBuilder;
use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTree\Page;
use Crell\MiDy\PageTree\PhysicalPath;
use Crell\Carica\Router\RouteResult;
use Crell\Carica\Router\RouteSuccess;
use Crell\MiDy\Services\ResponseCacher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class StaticFileHandler implements PageHandler
{
    private(set) array $supportedMethods = ['GET'];
    public array $supportedExtensions {
        get => array_keys($this->config->allowedExtensions);
    }

    public function __construct(
        private readonly ResponseCacher $cacher,
        private readonly ResponseBuilder $builder,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly StaticRoutes $config,
    ) {}

    public function handle(ServerRequestInterface $request, Page $page, string $ext): ?RouteResult
    {
        $contentType = $this->config->allowedExtensions[$ext];

        return new RouteSuccess(
            action: $this->action(...),
            arguments: [
                'file' => $page->variant($ext)->physicalPath,
                'contentType' => $contentType,
            ],
        );
    }

    public function action(ServerRequestInterface $request, PhysicalPath $file, string $contentType): ResponseInterface
    {
        return $this->cacher->handleCacheableFileRequest($request, (string)$file, function () use ($file, $contentType) {
            $stream = $this->streamFactory->createStreamFromFile((string)$file);
            $stream->rewind();
            return $this->builder->ok($stream, $contentType);
        });
    }
}
