<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class AggregatePage implements Page
{
    protected Page $activePage { get => $this->activePage ??= array_values($this->variants)[0]; }

    public private(set) string $title { get => $this->title ??= $this->activePage->title
        ?: ucfirst(pathinfo($this->logicalPath, PATHINFO_FILENAME)); }
    public string $summary { get => $this->activePage->summary; }
    public array $tags { get => $this->activePage->tags; }
    public string $slug { get => $this->activePage->slug ?? ''; }
    public bool $hidden { get => $this->activePage->hidden; }

    public bool $routable { get => $this->activePage->routable; }
    public string $path { get => $this->logicalPath; }

    public function __construct(
        protected readonly string $logicalPath,
        protected readonly array $variants,
    ) {}

    /**
     * @inheritDoc
     */
    public function variants(): array
    {
        return $this->variants;
    }

    public function variant(string $ext): ?Page
    {
        return $this->variants[$ext] ?? null;
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

    public function hasAnyTag(string ...$tags): bool
    {
        return $this->activePage->hasAnyTag(...$tags);
    }

    public function hasAllTags(string ...$tags): bool
    {
        return $this->activePage->hasAllTags(...$tags);
    }
}
