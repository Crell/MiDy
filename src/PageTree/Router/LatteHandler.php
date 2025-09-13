<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Router;

use Crell\Carica\ExplicitActionMetadata;
use Crell\MiDy\PageTree\Page;
use Crell\MiDy\PageTree\PhysicalPath;
use Crell\Carica\Router\RouteResult;
use Crell\Carica\Router\RouteSuccess;
use Crell\Carica\ResponseBuilder;
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
            arguments: [
                'file' => $page->variant($ext)->physicalPath,
                'page' => $page,
                'query' =>  $request->getQueryParams(),
            ],
            actionDef: new ExplicitActionMetadata(
                parameterTypes: [
                    'request' => ServerRequestInterface::class,
                    'file' => PhysicalPath::class,
                    'page' => Page::class,
                    'query' => 'array',
                ],
                requestParameter: 'request',
            ),
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
