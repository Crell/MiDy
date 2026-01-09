<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

interface Page extends Frontmatter
{
    public string $path { get; }

    public PhysicalPath $physicalPath { get; }
    public string $folder { get; }

    /**
     * @return array<Page>
     */
    public function variants(): array;
    public function variant(string $ext): ?Page;

    /**
     * Still not a huge fan of this, but...
     *
     * @return list<string>
     */
    public function getTrailingPath(string $fullPath): array;
}
