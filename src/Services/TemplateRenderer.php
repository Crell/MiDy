<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

use Latte\Engine;
use Psr\EventDispatcher\EventDispatcherInterface;

readonly class TemplateRenderer
{
    public function __construct(
        private Engine $latte,
        private EventDispatcherInterface $dispatcher,
    ) {}

    public function render(string $template, object|array $args = []): string
    {
        /** @var TemplatePreRender $event */
        $event = $this->dispatcher->dispatch(new TemplatePreRender($template, $args));

        return $this->latte->renderToString($event->template, $event->args);
    }
}

