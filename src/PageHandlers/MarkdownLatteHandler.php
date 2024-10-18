<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlers;

use Crell\MiDy\Config\MarkdownLatteConfiguration;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\PageTree\Page;
use Crell\MiDy\Router\HandlerRouter\PageHandler;
use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\Router\RouteSuccess;
use Crell\MiDy\Services\ResponseBuilder;
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
        private ResponseBuilder $builder,
        private MarkdownPageLoader $loader,
        private string $templateRoot,
        private TemplateRenderer $renderer,
        private MarkdownLatteConfiguration $config,
        private ConverterInterface $converter,
    ) {}

    public function handle(ServerRequestInterface $request, Page $page, string $ext): ?RouteResult
    {
        return new RouteSuccess(
            action: $this->action(...),
            method: 'GET',
            vars: [
                'file' => $page->variant($ext)->physicalPath,
            ],
        );
    }

    public function action(string $file): ResponseInterface
    {
        $page = $this->loader->load($file);

        $template = $this->templateRoot . '/' . ($page->template ?: $this->config->defaultPageTemplate);
        $args = $page->toTemplateParameters();
        // Pre-render the Content rather than making the template do it.
        $args['content'] = new Html($this->converter->convert($page->content));
        $output = $this->renderer->render($template, $args);

        return $this->builder->ok($output, 'text/html');
    }
}
