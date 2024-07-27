<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

use Psr\Http\Message\ServerRequestInterface;

class DelegatingRouter implements Router
{
    /** @var array<Router> */
    private array $delegates = [];

    public function __construct(
        private readonly Router $default,
    ) {}

    public function delegateTo(string $prefix, Router $router): void
    {
        $this->delegates[$prefix] = $router;
    }

    public function route(ServerRequestInterface $request): RouteResult
    {
        $requestPath = new RequestPath($request->getUri()->getPath());

        $registeredPrefixes = array_keys($this->delegates);

        foreach ($requestPath->prefixes as $requestPrefix) {
            if (in_array($requestPrefix, $registeredPrefixes, true)) {
                $result = $this->delegates[$requestPrefix]->route($request);
                if (! $result instanceof RouteNotFound) {
                    return $result;
                }
            }
        }

        return $this->default->route($request);
    }
}
