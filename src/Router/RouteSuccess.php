<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

readonly class RouteSuccess extends RouteResult
{
    /**
     * @param array<string, string> $parameters
     *   A map of parameters to the action, keyed by name, with their type as the value.
     * @param array<string, mixed> $vars
     *   The placeholder arguments extracted from the route path.
     *
     */
    public function __construct(
        public string|\Closure $action,
        public string $method,
        public ?string $permission = null,
        public array $parameters = [],
        public array $vars = [],
    ) {}

    /**
     * @param array<string, mixed> $vars
     */
    public function withAddedVars(array $vars): self
    {
        return new self(
            action: $this->action,
            method: $this->method,
            permission: $this->permission,
            parameters: $this->parameters,
            vars: $vars + $this->vars,
        );
    }
}
