<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

readonly class RequestPath
{
    public string $ext;
    public array $prefixes;
    public string $normalizedPath;

    public function __construct(public string $requestPath)
    {
        if (str_contains($this->requestPath, '.')) {
            [$this->normalizedPath, $this->ext] = \explode('.', $this->requestPath);
        } else {
            $this->normalizedPath = $this->requestPath;
            $this->ext = '*';
        }

        $pathParts = \explode('/', \trim($this->normalizedPath, '/'));

        $prefixes = array_reverse(array_reduce($pathParts, $this->reducer(...), []));
        $prefixes[] = '/';
        $this->prefixes = $prefixes;
    }

    private function reducer(array $carry, string $item): array
    {
        $carry[] = end($carry) . '/' . $item;
        return $carry;
    }
}
