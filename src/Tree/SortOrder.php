<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

enum SortOrder
{
    case Asc;
    case Desc;

    public static function fromString(string $order): ?self
    {
        return match (strtolower($order)) {
            'asc' => self::Asc,
            'desc' => self::Desc,
            default => null,
        };
    }
}
