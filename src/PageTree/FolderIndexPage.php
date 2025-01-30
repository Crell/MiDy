<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

/**
 * Pass through all page functionality to the index page, if any.
 *
 * This is a trait used only by Folder, basically just for code organization.
 * It's easier than having the properties and methods split into separate sections.
 */
trait FolderIndexPage
{
    public private(set) string $title {
        get => $this->title ??=
            $this->indexPage?->title
            ?? ucfirst(pathinfo($this->logicalPath, PATHINFO_FILENAME));
    }
    public private(set) string $summary { get => $this->summary ??= $this->indexPage?->summary ?? ''; }
    public private(set) array $tags { get => $this->tags ??= $this->indexPage?->tags ?? []; }
    public private(set) string $slug { get => $this->slug ??= $this->indexPage?->slug ?? ''; }
    public private(set) bool $hidden { get => $this->hidden ??= $this->indexPage?->hidden ?? true; }

    public bool $routable { get => $this->indexPage !== null; }
    public private(set) string $path { get => $this->path ??= str_replace('/index', '', $this->indexPage?->path ?? $this->logicalPath); }

    public private(set) string $name { get => $this->name ??= $this->indexPage?->name ?? ''; }

    public function variants(): array
    {
        return $this->indexPage?->variants() ?? [];
    }

    public function variant(string $ext): ?PageFile
    {
        return $this->indexPage?->variant($ext);
    }

    public function getTrailingPath(string $fullPath): array
    {
        return $this->indexPage?->getTrailingPath($fullPath) ?? [];
    }

    public function hasAnyTag(string ...$tags): bool
    {
        return $this->indexPage?->hasAnyTag(...$tags) ?? false;
    }

    public function hasAllTags(string ...$tags): bool
    {
        return $this->indexPage?->hasAllTags(...$tags) ?? false;
    }
}
