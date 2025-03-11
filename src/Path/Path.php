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

    public protected(set) string $end {
        get => $this->end ??= $this->segments[array_key_last($this->segments)] ?? '';
    }

    protected bool $concatable = true;

    /**
     * Path itself cannot be constructed externally. Only via the static method.
     */
    protected function __construct() {}

    protected function deriveIsFile(): bool
    {
        return str_contains($this->end, '.');
    }

    public static function create(string $path): Path
    {
        $class = self::getClass($path);
        return $class::createFromString($path);
    }

    abstract protected static function createFromString(string $path);

    protected static function getClass(string|PathFragment $path): string
    {
        if ($path instanceof PathFragment) {
            return PathFragment::class;
        }

        return match (true) {
            str_starts_with($path, '/'), str_contains($path, AbsolutePath::StreamSeparator) => AbsolutePath::class,
            default => PathFragment::class,
        };
    }

    public function concat(string|Path $fragment): Path
    {
        if ($this->isFile) {
            throw new \InvalidArgumentException('Cannot append a path fragment onto a path to a file.');
        }

        if (is_string($fragment)) {
            return $this->concat(self::create($fragment));
        }

        if (! $fragment->concatable) {
            throw new \InvalidArgumentException('Stream-based paths may not be used to append to an existing path');
        }

        $combinedSegments = [...$this->segments, ...$fragment->segments];

        if ($this instanceof AbsolutePath) {
            return static::createFromSegments($combinedSegments, $this->stream);
        }

        return static::createFromSegments($combinedSegments);
    }

    public function __toString(): string
    {
        return $this->path;
    }

    abstract protected function deriveParent(): static;
}
