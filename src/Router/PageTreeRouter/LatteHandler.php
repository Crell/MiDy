<?php

declare(strict_types=1);

namespace Crell\MiDy\Router\PageTreeRouter;

use Crell\MiDy\PageTree\Page;
use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\Router\RouteSuccess;
use Crell\MiDy\Services\ResponseBuilder;
use Crell\MiDy\Services\TemplateRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LatteHandler implements PageHandler
{
    public private(set) array $supportedMethods = ['GET'];
    public private(set) array $supportedExtensions = ['latte'];

    public function __construct(
        private readonly ResponseBuilder $builder,
        private readonly TemplateRenderer $renderer,
    ) {}

    public function handle(ServerRequestInterface $request, Page $page, string $ext): ?RouteResult
    {
        return new RouteSuccess(
            action: $this->action(...),
            method: 'GET',
            vars: [
                'file' => $page->variant($ext)->physicalPath,
                'query' =>  $request->getQueryParams(),
            ],
        );
    }

    public function action(ServerRequestInterface $request, string $file, array $query): ResponseInterface
    {
        return $this->builder->handleCacheableFileRequest($request, $file, function () use ($file, $query) {
            $page = $this->renderer->render($file, ['query' => $query]);
            return $this->builder->ok($page, 'text/html');
        });
    }
}
