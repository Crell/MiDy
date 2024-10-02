<?php

declare(strict_types=1);

use Crell\MiDy\Services\ResponseBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DoesntMatter
{
    public function __construct(
        private readonly ResponseBuilder $builder
    ) {}

    public function post(ServerRequestInterface $request): ResponseInterface
    {
        $form = $request->getParsedBody();
        return $this->builder->ok("POST received: " . $form['name']);
    }
}
