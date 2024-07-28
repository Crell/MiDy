<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlers;

use Crell\MiDy\Config\TemplateVariables;
use Crell\MiDy\Router\PageHandler;
use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\Router\RouteSuccess;
use Crell\MiDy\Services\ResponseBuilder;
use Latte\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class LatteHandler implements PageHandler
{
    public function __construct(
        private ResponseBuilder $builder,
        private Engine $latte,
        private TemplateVariables $templateVariables,
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
        $page = $this->latte->renderToString($file, $this->templateVariables->variables);

        return $this->builder->ok($page);
    }
}
