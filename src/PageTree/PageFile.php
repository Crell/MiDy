<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class PageFile implements Page
{
    public private(set) string $title {
        get => $this->title
            ??= $this->info->title
            ?: ucfirst(pathinfo($this->logicalPath, PATHINFO_FILENAME));
    }
    public string $summary { get => $this->info->summary; }
    public array $tags { get => $this->info->tags; }
    public string $slug { get => $this->info->slug ?? ''; }
    public bool $hidden { get => $this->info->hidden; }

    public bool $routable { get => true; }
    public string $path { get => $this->logicalPath; }

    public function __construct(
        public readonly string $physicalPath,
        public readonly string $logicalPath,
        public readonly string $ext,
        public readonly int $mtime,
        public readonly PageInformation $info,
    ) {}

    public function hasAnyTag(string ...$tags): bool
    {
        return $this->info->hasAnyTag(...$tags);
    }

    public function hasAllTags(string ...$tags): bool
    {
        return $this->info->hasAllTags(...$tags);
    }

    public function variants(): array
    {
        return [$this->ext => $this];
    }

    public function variant(string $ext): ?Page
    {
        return $ext === $this->ext ? $this : null;
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
