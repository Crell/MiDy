<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

readonly class PageFile implements Page
{
    public function __construct(
        public string $physicalPath,
        public string $logicalPath,
        public string $ext,
        public int $mtime,
        public PageInformation $info,
    ) {}

    public function title(): string
    {
        return $this->info->title()
            ?: ucfirst(pathinfo($this->logicalPath, PATHINFO_FILENAME));
    }

    public function summary(): string
    {
        return $this->info->summary() ?? '';
    }

    public function tags(): array
    {
        return $this->info->tags() ?? [];
    }

    public function slug(): ?string
    {
        return $this->info->slug() ?? '';
    }

    public function hidden(): bool
    {
        return $this->info->hidden() ?? false;
    }

    public function path(): string
    {
        return $this->logicalPath;
    }

    public function routable(): true
    {
        return true;
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
