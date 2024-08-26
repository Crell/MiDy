<?php

declare(strict_types=1);

namespace Crell\MiDy\TimedCache;

interface TimedCache
{
    public function get(string $key, int|\DateTimeInterface $sourceLastModified, \Closure $regenerator): mixed;

    public function write(string $key, mixed $data): bool;

    public function delete(string $key): void;

    public function clear(int|\DateTimeInterface|null $olderThan = null): void;
}
