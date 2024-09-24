<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class Page implements Linkable, MultiType, MiDyFrontMatter
{
    // @todo Need to make this non-mutable somehow, while still allowing limitTo() or equivalent.
    public int $lastModified;

    // @todo Store info about each variant here, pulled from SplFileInfo.

     /**
      * @param string $logicalPath
      * @param array<string, RouteFile> $variants
      */
    public function __construct(
        private readonly string $logicalPath,
        protected array $variants,
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

    public function summary(): string
    {
        return reset($this->variants)->frontmatter->summary();
    }

    public function tags(): array
    {
        return reset($this->variants)->frontmatter->tags();
    }

    public function slug(): ?string
    {
        return reset($this->variants)->frontmatter->slug();
    }

    // @todo Make this better.
    public function title(): string
    {
        return reset($this->variants)->title();
    }

    public function path(): string
    {
        return $this->logicalPath;
    }

    public function hidden(): bool
    {
        return reset($this->variants)->frontmatter->hidden();
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
