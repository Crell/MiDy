<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

interface Page extends PageInformation
{
    public function routable(): bool;
    public function path(): string;

    /**
     * @return array<Page>
     */
    public function variants(): array;
    public function variant(string $ext): ?Page;
    // Still not a huge fan of this, but...
    public function getTrailingPath(string $fullPath): array;
}
