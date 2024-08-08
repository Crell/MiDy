<?php

declare(strict_types=1);

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

    public function post(ServerRequestInterface $request): ResponseInterface
    {
        $form = $request->getParsedBody();
        return $this->builder->ok("POST received: " . $form['name']);
    }
};
