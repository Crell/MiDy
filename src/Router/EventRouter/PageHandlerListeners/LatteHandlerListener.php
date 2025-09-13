<?php

declare(strict_types=1);

namespace Crell\MiDy\Router\EventRouter\PageHandlerListeners;

use Crell\MiDy\Router\EventRouter\RouteResolution;
use Crell\MiDy\Router\RouteSuccess;
use Crell\Carica\ResponseBuilder;
use Latte\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class LatteHandlerListener
{
    public function __construct(
        private ResponseBuilder $builder,
        private Engine $latte,
    ) {}

    public function __invoke(RouteResolution $event): void
    {
        if ($event->request->getMethod() !== 'GET') {
            return;
        }

        if (in_array("{$event->routesPath}{$event->requestPath->normalizedPath}.latte", $event->candidates, true)) {
            $event->routingResult(
                new RouteSuccess(
                    action: $this->action(...),
                    method: 'GET',
                    vars: [
                        'file' => "{$event->routesPath}{$event->requestPath->normalizedPath}.latte",
                    ],
                )
            );
        }
    }

    public function action(ServerRequestInterface $request, string $file): ResponseInterface
    {
        $page = $this->latte->renderToString($file);

        return $this->builder->ok($page);
    }
}
