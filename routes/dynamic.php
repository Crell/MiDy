<?php

declare(strict_types=1);

namespace App\Routes;

use Crell\MiDy\Services\ResponseBuilder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return new class($container) {

    private readonly ResponseBuilder $builder;

    public function __construct(
        ContainerInterface $container,
    ) {
        $this->builder = $container->get(ResponseBuilder::class);
    }

    public function get(ServerRequestInterface $request): ResponseInterface
    {
        return $this->builder->ok("GET received: " . $request->getUri()->getPath());
    }

    public function post(ServerRequestInterface $request): ResponseInterface
    {
        return $this->builder->ok("POST received: " . $request->getUri()->getPath());
    }
};
