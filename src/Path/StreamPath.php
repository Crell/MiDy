<?php

declare(strict_types=1);

namespace Crell\MiDy\Path;

class StreamPath extends Path
{
    public const string StreamSeparator = '://';

    public string $stream;

    public function __construct(string $path)
    {
        $this->path = $path;
        [$this->stream, $pathPart] = explode(self::StreamSeparator, $path);
        $this->segments = explode('/', $pathPart);

        $pathinfo = pathinfo($path);
        $this->ext = $pathinfo['extension'] ?? null;
    }

    protected function deriveParent(): static
    {
        if (count($this->segments) <= 1) {
            /** @var StreamPath */
            return static::fromString($this->stream . self::StreamSeparator);
        }
        return parent::deriveParent();
    }
}
