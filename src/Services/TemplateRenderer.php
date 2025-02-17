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

        // When running on the CLI (such as tests), headers_list() doesn't work.  If using XDebug,
        // it has its own headers method that we can use instead.  This is ugly,
        // but the only way I know of that gets tests to pass.
        // @see https://github.com/sebastianbergmann/phpunit/issues/3409#issuecomment-442596333
        $headers = (PHP_SAPI === 'cli' && function_exists('xdebug_get_headers'))
            ? xdebug_get_headers()
            : headers_list();
        // If no content type is found, assume it's HTML.
        $contentType = 'text/html';
        foreach (array_map(strtolower(...), $headers) as $header) {
            if (str_starts_with($header, 'content-type')) {
                sscanf($header, 'content-type: %s', $contentType);
                break;
            }
        }

        return new TemplateResult($rendered, $contentType);
    }
}
