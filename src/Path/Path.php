<?php

declare(strict_types=1);

namespace Crell\MiDy\Path;

abstract class Path implements \Stringable
{
    /** @var array<string> */
    protected protected(set) readonly array $segments;

    public protected(set) readonly ?string $ext;

    public bool $isFile {
        get => str_contains(end($this->segments), '.');
    }

    public static function fromString(string $path): Path
    {
        $class = PathFragment::class;
        if (str_starts_with($path, '/')) {
            $class = AbsolutePath::class;
        }
        if (str_contains($path, StreamPath::StreamSeparator)) {
            $class = StreamPath::class;
        }

        return new $class($path);
    }

    public function __toString(): string
    {
        return implode('/', $this->segments);
    }
}
