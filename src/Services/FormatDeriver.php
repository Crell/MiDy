<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

readonly class FormatDeriver
{
    /**
     * This is clearly not the correct logic, but it's just a stub for now.
     */
    public function mapType(string $mimeType): string
    {
        if (str_contains($mimeType, 'application/json')) {
            return 'json';
        }
        if (str_contains($mimeType, 'text/html')) {
            return 'html';
        }
        return 'unknown';
    }
}
