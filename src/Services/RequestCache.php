<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestCache
{
    private array $cache = [];

    public function getResponseFor(ServerRequestInterface $request): ?ResponseInterface
    {
        return $this->cache[$this->makeKey($request)] ?? null;
    }

    public function setResponseFor(ServerRequestInterface $request, ResponseInterface $response): static
    {
        $this->cache[$this->makeKey($request)] = $response;
        return $this;
    }

    private function makeKey(ServerRequestInterface $request): string
    {
        $key = [
            $request->getUri()->getHost(),
            $request->getUri()->getPath(),
            $request->getHeader('accept')[0],
        ];

        return implode(':', $key);
    }
}
