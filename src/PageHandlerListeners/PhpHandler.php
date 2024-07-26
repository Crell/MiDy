<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlers;

use Crell\MiDy\Router\RouteResolution;
use Crell\MiDy\Router\RouteSuccess;
use Psr\Container\ContainerInterface;

class PhpHandler
{
    private array $supportedMethods = [
        'get', 'post', 'head', 'put', 'delete'
    ];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public function __invoke(RouteResolution $event): void
    {
        if (in_array("{$event->path}.php", $event->candidates, true)) {
            $actionObject = $this->loadAction("{$event->path}.php");

            $method = $event->request->getMethod();
            if (method_exists($actionObject, $method)) {
                $event->routingResult(
                    new RouteSuccess(
                        action: $actionObject->$method(...),
                        method: strtoupper($method),
                        vars: [
                            'file' => "{$event->path}.php",
                        ],
                    )
                );
            }
        }
    }

    private function loadAction(string $file): object
    {
        $container = $this->container;
        return require($file);
    }
}
