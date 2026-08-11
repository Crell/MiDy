<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Router;

use Crell\Carica\ExplicitActionMetadata;
use Crell\Carica\ResponseBuilder;
use Crell\Carica\Router\RouteResult;
use Crell\Carica\Router\RouteSuccess;
use Crell\MiDy\Config\MarkdownLatteConfiguration;
use Crell\MiDy\LatteTheme\LatteThemeExtension;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\PageTree\Page;
use Crell\MiDy\PageTree\PhysicalPath;
use Crell\MiDy\Services\ResponseCacher;
use Crell\MiDy\Services\TemplateRenderer;
use Latte\Runtime\Html;
use League\CommonMark\ConverterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MarkdownLatteHandler implements PageHandler
{
    public private(set) array $supportedMethods = ['GET'];
    public private(set) array $supportedExtensions = ['md'];

    public function __construct(
        private readonly ResponseBuilder $builder,
        private readonly ResponseCacher $cacher,
        private readonly MarkdownPageLoader $loader,
        private readonly LatteThemeExtension $themeExtension,
        private readonly TemplateRenderer $renderer,
        private readonly MarkdownLatteConfiguration $config,
        private readonly ConverterInterface $converter,
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

    /**
     * @param array<string, string|int|float> $query
     */
    public function action(ServerRequestInterface $request, Page $page, PhysicalPath $file, array $query): ResponseInterface
    {
        return $this->cacher->handleCacheableFileRequest($request, (string)$file, function() use ($file, $page, $query) {
            $mdPage = $this->loader->load((string)$file);

            $template = $this->themeExtension->findTemplatePath($page->other['template'] ?? $this->config->defaultPageTemplate);

            $args['currentPage'] = $page;
            $args['query'] = new HttpQuery($query);
            // Pre-render the Content rather than making the template do it.
            $args['content'] = new Html($this->converter->convert($mdPage->content));

            $result = $this->renderer->render($template, $args);

            return $this->builder->ok($result->content, $result->contentType);
        });
    }
}
