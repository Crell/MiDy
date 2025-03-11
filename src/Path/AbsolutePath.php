<?php

declare(strict_types=1);

namespace Crell\MiDy\Path;

class AbsolutePath extends Path
{
    public const string StreamSeparator = '://';

    public readonly ?string $stream;

    protected static function createFromString(string $path)
    {
        $new = new static();
        $new->path = $path;

        if (str_contains($path, self::StreamSeparator)) {
            [$new->stream, $pathPart] = explode(self::StreamSeparator, $path);
            $new->segments = explode('/', $pathPart);
            $new->concatable = false;
        } else {
            $new->segments = array_filter(explode('/', $path));
            $new->stream = null;
        }

        $new->ext = pathinfo($new->path, PATHINFO_EXTENSION);

        return $new;
    }

    protected static function createFromSegments(array $segments, ?string $stream = null): static
    {
        $new = new static();

        $new->segments = $segments;

        $new->path = ($stream ? $stream . self::StreamSeparator : '/') . implode('/', $segments);
        $new->stream = $stream;
        $new->ext = pathinfo($new->path, PATHINFO_EXTENSION);

        return $new;
    }

    protected function deriveParent(): static
    {
        $segments = $this->segments;
        array_pop($segments);
        return static::createFromSegments($segments, $this->stream);
    }
}
