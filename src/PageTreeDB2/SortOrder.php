<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

enum SortOrder: string
{
    case Asc = 'Asc';
    case Desc = 'Desc';

    public static function fromString(?string $order): ?self
    {
        if (!$order) {
            return null;
        }
        return match (strtolower($order)) {
            'asc' => self::Asc,
            'desc' => self::Desc,
            default => null,
        };
    }
}
