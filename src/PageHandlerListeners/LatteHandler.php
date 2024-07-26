<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlers;

use Crell\MiDy\Router\RouteResolution;
use Crell\MiDy\Router\RouteSuccess;
use Crell\MiDy\Services\ResponseBuilder;
use Latte\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class LatteHandler
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

        if (in_array("{$event->path}.latte", $event->candidates, true)) {
            $event->routingResult(
                new RouteSuccess(
                    action: $this->action(...),
                    method: 'GET',
                    vars: [
                        'file' => "{$event->path}.latte",
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
