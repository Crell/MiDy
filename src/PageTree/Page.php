<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class Page implements Linkable, MultiType
{
    // @todo Need to make this non-mutable somehow, while still allowing limitTo() or equivalent.
    public int $lastModified;

    // @todo Store info about each variant here, pulled from SplFileInfo.

     /**
      * @param string $logicalPath
      * @param array<string, RouteFile> $variants
      * @param bool $hidden
      *   If true, this page is hidden from navigation but still routable.
      */
    public function __construct(
        private readonly string $logicalPath,
        protected array $variants,
        public readonly bool $hidden = false,
    ) {
        $this->lastModified = count($this->variants) ? max(array_map(static fn(RouteFile $r) => $r->mtime, $variants)) : 0;
    }

    // @todo This is a bad approach, and a sign that we need to merge Page and RouteFile into a single interface, probably.
    public function limitTo(string $variant): static
    {
        $new = new Page($this->logicalPath, []);
        $new->variants[$variant] = $this->variants[$variant];
        $new->lastModified = $this->lastModified;
        return $new;
    }

    public function variants(): array
    {
        return $this->variants;
    }

    public function variant(string $ext): ?RouteFile
    {
        return $this->variants[$ext] ?? null;
    }

    // @todo Make this better.
    public function title(): string
    {
        return reset($this->variants)->title;
    }

    public function path(): string
    {
        return $this->logicalPath;
    }

    public function getTrailingPath(string $fullPath): array
    {
        if (!str_starts_with($fullPath, $this->logicalPath)) {
            return [];
        }

        // If the path ends with an extension, then we assume it's a file
        // and there's no trailing necessary.
        if (pathinfo($fullPath, PATHINFO_EXTENSION)) {
            return [];
        }

        return array_values(array_filter(explode('/', substr($fullPath, strlen($this->logicalPath)))));
    }
}
