<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

/**
 * @todo This is begging to use interface properties instead...
 */
interface PageInformation extends Hidable
{
    public function title(): string;
    public function summary(): string;
    public function tags(): array;
    public function slug(): ?string;

    public function hasAnyTag(string ...$tags): bool;
    public function hasAllTags(string ...$tags): bool;
}
