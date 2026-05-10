<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

enum FileParserError
{
    case FileNotSupported;

    public function message(): string
    {
        return match ($this) {
            self::FileNotSupported => 'File not supported',
        };
    }
}
