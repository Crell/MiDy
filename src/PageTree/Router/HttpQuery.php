<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Router;

/**
 * @todo This likely belongs in a different namespace.
 */
readonly class HttpQuery
{
    public function __construct(private array $params = []) {}

    public function with(...$changes): HttpQuery
    {
        return new self($changes + $this->params);
    }

    public function getString(string $name, ?string $default = null): ?string
    {
        if (array_key_exists($name, $this->params)) {
            // In case the value is numeric, cast it to a string.
            return (string)$this->params[$name];
        }
        return $default;
    }

    public function getInt(string $name, ?int $default = null): ?int
    {
        // If there is a value but it's non-integer, it doesn't count.
        if (array_key_exists($name, $this->params)) {
            return filter_var($this->params[$name], FILTER_VALIDATE_INT) ?: $default;
        }

        return $default;
    }

    /**
     * Fetches a natural number (0 or positive int) from the query.
     */
    public function getNum(string $name, ?int $default = null): ?int
    {
        $val = $this->getInt($name, $default);
        return $val >= 0 ? $val : $default;
    }

    public function getFloat(string $name, ?float $default = null): ?float
    {
        // If there is a value but it's non-numeric, it doesn't count.
        if (array_key_exists($name, $this->params)) {
            return filter_var($this->params[$name], FILTER_VALIDATE_FLOAT) ?: $default;
        }

        return $default;

    }

    public function toArray(): array
    {
        return $this->params;
    }
}
