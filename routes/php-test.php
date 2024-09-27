<?php

declare(strict_types=1);

use Crell\MiDy\Services\ResponseBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PhpTest
{
    public function __construct(
        private readonly ResponseBuilder $builder,
    ) {}

    public function get(ServerRequestInterface $request): ResponseInterface
    {
        return $this->builder->ok("GET received: " . $request->getUri()->getPath());
    }

    public function post(ServerRequestInterface $request): ResponseInterface
    {
        return $this->builder->ok("POST received: " . $request->getUri()->getPath());
    }
}
