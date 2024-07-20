<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\Router\RouteSuccess;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class RuntimeActionInvoker implements ActionInvoker
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function invokeAction(ServerRequestInterface $request): mixed
    {
        /** @var RouteSuccess $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);

        $action = $routeResult?->action;

        $definedParams = match (true) {
            is_string($action) => $this->getInvokableClassParameters($action),
            is_callable($action) => $this->getCallableParameters($action),
        };

        $available = $routeResult->vars;

        // Have to check the types to avoid possible name collisions.
        foreach ($definedParams as $name => $type) {
            if (is_a($type, ServerRequestInterface::class, true)) {
                $available[$name] = $request;
            } elseif (is_a($type, RouteResult::class, true)) {
                // Not sure if this is a good one to include or not.
                $available[$name] = $routeResult;
            }
        }

        $args = array_intersect_key($available, $definedParams);

        if (is_string($action)) {
            $action = $this->container->get($routeResult->action);
        }

        return $action(...$args);
    }

    private function getCallableParameters(\Closure $action): array
    {
        $rAction = new \ReflectionFunction($action);
        foreach ($rAction?->getParameters() ?? [] as $rParam) {
            $params[$rParam->name] = $rParam->getType()?->getName();
        }
        return $params;
    }

    private function getInvokableClassParameters(string $action): array
    {
        $params = [];
        $rAction = new \ReflectionClass($action);
        foreach ($rAction?->getMethod('__invoke')?->getParameters() ?? [] as $rParam) {
            $params[$rParam->name] = $rParam->getType()?->getName();
        }
        return $params;

    }
}
