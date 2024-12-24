<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

class PageFile implements Page
{
    public string $title { get => $this->file->title; }
    public string $summary { get => $this->file->summary; }
    public array $tags { get => $this->file->frontmatter->tags; }
    public ?string $slug { get => $this->file->frontmatter->slug; }
    public bool $hidden { get => $this->file->hidden; }

    public string $name { get => $this->file->pathName; }

    public bool $routable { get => $this->file->routable; }
    public string $path { get => $this->file->logicalPath; }

    public string $physicalPath { get => $this->file->physicalPath; }

    public string $ext { get => $this->file->ext; }

    public function __construct(
        private readonly ParsedFile $file,
    ) {}

    public function variants(): array
    {
        return [$this->file->ext => $this];
    }

    public function variant(string $ext): ?PageFile
    {
        return $ext === $this->file->ext ? $this : null;
    }

    public function getTrailingPath(string $fullPath): array
    {
        if (!str_starts_with($fullPath, $this->file->logicalPath)) {
            return [];
        }

        // If the path ends with an extension, then we assume it's a file
        // and there's no trailing necessary.
        if (pathinfo($fullPath, PATHINFO_EXTENSION)) {
            return [];
        }

        return array_values(array_filter(explode('/', substr($fullPath, strlen($this->file->logicalPath)))));
    }

    public function hasAnyTag(string ...$tags): bool
    {
        return $this->file->frontmatter->hasAnyTag(...$tags);
    }

    public function hasAllTags(string ...$tags): bool
    {
        return $this->file->frontmatter->hasAllTags(...$tags);
    }

    public function __debugInfo(): ?array
    {
        return [
            'logicalPath' => $this->path,
            'ext' => $this->file->ext,
            'title' => $this->title,
            'routable' => $this->routable,
            'hidden' => $this->hidden,
        ];
    }
}