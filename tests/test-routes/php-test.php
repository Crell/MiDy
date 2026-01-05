<?php

declare(strict_types=1);

use Crell\Carica\ResponseBuilder;
use Crell\MiDy\Services\ResponseCacher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PhpTest
{
    public function __construct(
        private readonly ResponseCacher $cacher,
        private readonly ResponseBuilder $builder,
    ) {}

    public function get(ServerRequestInterface $request): ResponseInterface
    {
        return $this->cacher->handleCacheableFileRequest($request, __FILE__, function () use ($request) {
            return $this->builder->ok("GET received: " . $request->getUri()->getPath(), 'text/plain');
        });
    }

    public function post(ServerRequestInterface $request): ResponseInterface
    {
        return $this->builder->ok("POST received: " . $request->getUri()->getPath());
    }
}
