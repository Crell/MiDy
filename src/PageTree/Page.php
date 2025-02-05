<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\PageTree\Model\Frontmatter;

interface Page extends Frontmatter
{
    public string $path { get; }

    /**
     * @return array<Page>
     */
    public function variants(): array;
    public function variant(string $ext): ?Page;
    // Still not a huge fan of this, but...
    public function getTrailingPath(string $fullPath): array;
}
