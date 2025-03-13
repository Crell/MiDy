<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Router;

use Crell\MiDy\PageTree\Page;
use Crell\MiDy\PageTree\PhysicalPath;
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
                'page' => $page,
                'query' =>  $request->getQueryParams(),
            ],
        );
    }

    public function action(ServerRequestInterface $request, PhysicalPath $file, Page $page, array $query): ResponseInterface
    {
        return $this->builder->handleCacheableFileRequest($request, (string)$file, function () use ($file, $query, $page) {
            $result = $this->renderer->render((string)$file, [
                'query' => new HttpQuery($query),
                'currentPage' => $page,
            ]);
            return $this->builder->ok($result->content, $result->contentType);
        });
    }
}
