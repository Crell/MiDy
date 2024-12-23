<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

class AggregatePage implements Page
{
    // @TODO This could probably be better defined.
    protected Page $activeFile { get => $this->activeFile ??= array_values($this->variants)[0]; }

    public private(set) string $title {
        get => $this->title ??=
            $this->activeFile->title
            ?: ucfirst(pathinfo($this->logicalPath, PATHINFO_FILENAME));
        }
    public string $summary { get => $this->activeFile->summary; }
    public array $tags { get => $this->activeFile->tags; }
    public string $slug { get => $this->activeFile->slug ?? ''; }
    public bool $hidden { get => $this->activeFile->hidden; }

    public bool $routable { get => $this->activeFile->routable; }
    public string $path { get => $this->logicalPath; }

    /**
     * @param array<PageFile> $variants
     */
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

    public function variant(string $ext): ?PageFile
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
        return $this->activeFile->hasAnyTag(...$tags);
    }

    public function hasAllTags(string ...$tags): bool
    {
        return $this->activeFile->hasAllTags(...$tags);
    }

    public function __debugInfo(): ?array
    {
        return [
            'logicalPath' => $this->path,
            'exts' => array_keys($this->variants),
            'title' => $this->title,
            'routable' => $this->routable,
            'hidden' => $this->hidden,
        ];
    }
}
