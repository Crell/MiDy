<?php

declare(strict_types=1);

namespace Crell\MiDy\Path;

abstract class Path implements \Stringable
{
    /** @var array<string> */
    protected protected(set) readonly array $segments;

    public protected(set) readonly ?string $ext;

    public bool $isFile {
        get => str_contains($this->segments[array_key_last($this->segments)], '.');
    }

    public bool $appendable {
        get => ! $this->isFile;
    }

    protected protected(set) readonly string $path;

    public static function fromString(string $path): Path
    {
        $class = self::getClass($path);
        return new $class($path);
    }

    protected static function getClass(string $path): string
    {
        $class = PathFragment::class;
        if (str_starts_with($path, '/')) {
            $class = AbsolutePath::class;
        }
        if (str_contains($path, StreamPath::StreamSeparator)) {
            $class = StreamPath::class;
        }

        return $class;
    }

    public function append(string $fragment): Path
    {
        if (! $this->appendable) {
            throw new \InvalidArgumentException('Cannot append a path fragment onto a path to a file.');
        }

        $class = self::getClass($fragment);
        return match ($class) {
            PathFragment::class => static::fromString($this->path . '/' . $fragment),
            AbsolutePath::class => static::fromString($this->path . $fragment),
            default => throw new \InvalidArgumentException('StreamPaths may not be used to append to an existing path')
        };
    }

    public function __toString(): string
    {
        return implode('/', $this->segments);
    }
}
