<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

interface Page extends PageInformation
{
    public bool $routable { get; }
    public string $path { get; }

    /**
     * The (file) name of this particular page, without any path.
     */
    public string $name { get; }

    /**
     * @return array<Page>
     */
    public function variants(): array;
    public function variant(string $ext): ?PageFile;
    // Still not a huge fan of this, but...
    public function getTrailingPath(string $fullPath): array;
}
