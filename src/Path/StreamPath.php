<?php

declare(strict_types=1);

namespace Crell\MiDy\Path;

class StreamPath extends Path
{
    public const string StreamSeparator = '://';

    public readonly string $stream;

    protected static function createFromString(string $path)
    {
        $new = new static();
        $new->path = $path;
        [$new->stream, $pathPart] = explode(self::StreamSeparator, $path);
        $new->segments = explode('/', $pathPart);

        $new->ext = pathinfo($new->path, PATHINFO_EXTENSION);

        return $new;
    }

    protected static function createFromSegments(array $segments, string $stream): static
    {
        $new = new static();

        $new->segments = $segments;
        $new->stream = $stream;

        $new->path = $stream . self::StreamSeparator . implode('/', $segments);

        $new->ext = pathinfo($new->path, PATHINFO_EXTENSION);

        return $new;
    }

    protected function deriveParent(): static
    {
        if (count($this->segments) <= 1) {
            /** @var StreamPath */
            return static::create($this->stream . self::StreamSeparator);
        }
        return parent::deriveParent();
    }
}
