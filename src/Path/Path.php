<?php

declare(strict_types=1);

namespace Crell\MiDy\Path;

abstract class Path implements \Stringable
{
    /** @var array<string> */
    protected protected(set) array $segments {
        get => $this->segments;
        set => $value;
    }

    public protected(set) readonly ?string $ext;

    public protected(set) bool $isFile {
        get => $this->isFile ??= $this->deriveIsFile();
    }

    public protected(set) Path $parent {
        get => $this->parent ??= $this->deriveParent();
    }

    protected protected(set) readonly string $path;

    /**
     * Path itself cannot be constructed externally. Only via the static method.
     */
    protected function __construct() {}

    protected function deriveIsFile(): bool
    {
        return str_contains($this->segments[array_key_last($this->segments)], '.');
    }

    public static function create(string $path): Path
    {
        $class = self::getClass($path);
        return $class::createFromString($path);
        return new $class($path);
    }

    protected static abstract function createFromString(string $path);

    protected static function getClass(string|PathFragment $path): string
    {
        if ($path instanceof PathFragment) {
            return PathFragment::class;
        }

        $class = PathFragment::class;
        if (str_starts_with($path, '/')) {
            $class = AbsolutePath::class;
        }
        if (str_contains($path, StreamPath::StreamSeparator)) {
            $class = StreamPath::class;
        }

        return $class;
    }

    public function concat(string|PathFragment $fragment): Path
    {
        if ($this->isFile) {
            throw new \InvalidArgumentException('Cannot append a path fragment onto a path to a file.');
        }

        if (self::getClass($fragment) === StreamPath::class) {
            throw new \InvalidArgumentException('StreamPaths may not be used to append to an existing path');
        }

        $fragSegments = $fragment instanceof self
            ? $fragment->segments
            : array_filter(explode('/', $fragment));

        $combinedSegments = [...$this->segments, ...$fragSegments];

        return match ($this::class) {
            PathFragment::class, AbsolutePath::class => $this::createFromSegments($combinedSegments),
            StreamPath::class => StreamPath::createFromSegments($combinedSegments, $this->stream),
        };
    }

    public function __toString(): string
    {
        return $this->path;
    }

    protected function deriveParent(): static
    {
        return self::create(dirname((string)$this));
    }
}
