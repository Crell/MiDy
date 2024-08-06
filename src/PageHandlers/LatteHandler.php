<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlers;

use Crell\MiDy\Router\HandlerRouter\PageHandler;
use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\Router\RouteSuccess;
use Crell\MiDy\Services\ResponseBuilder;
use Crell\MiDy\Services\TemplateRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class LatteHandler implements PageHandler
{
    public function __construct(
        private ResponseBuilder $builder,
        private TemplateRenderer $renderer,
    ) {}

    public function supportedMethods(): array
    {
        return ['GET'];
    }

    public function supportedExtensions(): array
    {
        return ['latte'];
    }

    public function handle(ServerRequestInterface $request, string $file, string $ext): ?RouteResult
    {
        return new RouteSuccess(
            action: $this->action(...),
            method: 'GET',
            vars: [
                'file' => $file,
            ],
        );
    }

    public function action(string $file): ResponseInterface
    {
        $page = $this->renderer->render($file);

        return $this->builder->ok($page);
    }
}
