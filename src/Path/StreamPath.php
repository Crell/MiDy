<?php

declare(strict_types=1);

namespace Crell\MiDy\Path;

class StreamPath extends Path
{
    public const string StreamSeparator = '://';

    public string $stream;

    public function __construct(string $path)
    {
        [$this->stream, $pathPart] = explode(self::StreamSeparator, $path);
        $this->segments = explode('/', $pathPart);

        $pathinfo = pathinfo($path);
        $this->ext = $pathinfo['extension'] ?? null;

    }

    public function __toString(): string
    {
        return $this->stream . self::StreamSeparator . parent::__toString();
    }
}
