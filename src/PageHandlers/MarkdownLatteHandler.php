<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlers;

use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\Router\PageHandler;
use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\Router\RouteSuccess;
use Crell\MiDy\Services\ResponseBuilder;
use Crell\MiDy\Services\TemplateRenderer;
use Latte\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class MarkdownLatteHandler implements PageHandler
{
    public function __construct(
        private ResponseBuilder $builder,
        private Engine $latte,
        private MarkdownPageLoader $loader,
        private string $templateRoot,
        private TemplateRenderer $renderer,
    ) {}

    public function supportedMethods(): array
    {
        return ['GET'];
    }

    public function supportedExtensions(): array
    {
        return ['md'];
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
        $page = $this->loader->load($file);
        $template = $this->templateRoot . '/' . ($page->template ?: 'blog-page.latte');
        $output = $this->renderer->render($template, $page->toTemplateParameters());

        return $this->builder->ok($output);
    }
}