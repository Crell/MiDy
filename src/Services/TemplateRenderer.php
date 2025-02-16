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

    public function render(string $template, object|array $args = []): TemplateResult
    {
        /** @var TemplatePreRender $event */
        $event = $this->dispatcher->dispatch(new TemplatePreRender($template, $args));

        // Latte doesn't offer an API to get the content type of the template.
        // And it only send sit when you tell it to print the rendered output
        // itself.  So this is this least-bad way to capture the content type,
        // until/unless Latte provides a useful API.
        ob_start();
        $this->latte->render($event->template, $event->args);
        $rendered = ob_get_clean();

        // If no content type is found, assume it's HTML.
        // @todo This seems to be failing only in tests, where header_list()
        //   comes back empty.
        $contentType = 'text/html';
        foreach (array_map(strtolower(...), headers_list()) as $header) {
            if (str_starts_with($header, 'content-type')) {
                sscanf($header, 'content-type: %s', $contentType);
                break;
            }
        }

        return new TemplateResult($rendered, $contentType);
    }
}
