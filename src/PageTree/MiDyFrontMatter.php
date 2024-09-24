<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

/**
 * @todo This is begging to use interface properties instead...
 */
interface MiDyFrontMatter
{
    public function title(): string;
    public function summary(): string;
    public function tags(): array;
    public function slug(): ?string;
    public function hidden(): bool;
}
