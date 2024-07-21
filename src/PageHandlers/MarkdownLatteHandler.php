<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlers;

use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\Router\RouteResolution;
use Crell\MiDy\Router\RouteSuccess;
use Crell\MiDy\Services\ResponseBuilder;
use Latte\Engine;
use Psr\Http\Message\ResponseInterface;

readonly class MarkdownLatteHandler
{
    public function __construct(
        private ResponseBuilder $builder,
        private Engine $latte,
        private MarkdownPageLoader $loader,
        private string $templateRoot,
    ) {}

    public function __invoke(RouteResolution $event): void
    {
        if ($event->request->getMethod() !== 'GET') {
            return;
        }

        if (in_array("{$event->path}.md", $event->candidates, true)) {
            $event->routingResult(
                new RouteSuccess(
                    action: $this->action(...),
                    method: 'GET',
                    vars: [
                        'file' => "{$event->path}.md",
                    ],
                )
            );
        }
    }

    public function action(string $file): ResponseInterface
    {
        $page = $this->loader->load($file);

        $template = $this->templateRoot . '/' . ($page->template ?: 'blog-page.latte');

        $output = $this->latte->renderToString($template, $page->toTemplateParameters());

        return $this->builder->ok($output);
    }
}
