<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

interface PageInformation
{
    public string $title { get; }
    public string $summary { get; }
    public array $tags { get; }
    public ?string $slug { get; }
    public bool $hidden { get; }

    public function hasAnyTag(string ...$tags): bool;
}
