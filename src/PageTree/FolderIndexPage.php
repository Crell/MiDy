<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use DateTimeImmutable;

/**
 * Pass through all page functionality to the index page, if any.
 *
 * This is a trait used only by Folder, basically just for code organization.
 * It's easier than having the properties and methods split into separate sections.
 */
trait FolderIndexPage
{
    private(set) string $title {
        get => $this->title ??=
            $this->indexPage?->title
            ?? ucfirst(pathinfo((string)$this->logicalPath, PATHINFO_FILENAME));
    }
    private(set) string $summary { get => $this->summary ??= $this->indexPage?->summary ?? ''; }
    private(set) array $tags { get => $this->tags ??= $this->indexPage?->tags ?? []; }
    private(set) string $slug { get => $this->slug ??= $this->indexPage?->slug ?? ''; }
    private(set) bool $hidden { get => $this->hidden ??= $this->indexPage?->hidden ?? true; }

    public bool $routable { get => $this->indexPage !== null; }
    private(set) string $path { get => $this->path ??= str_replace('/index', '', $this->indexPage?->path ?? $this->logicalPath); }

    private(set) string $name { get => $this->name ??= $this->indexPage?->name ?? ''; }
    private(set) array $other { get => $this->other ??= $this->indexPage?->other ?? []; }
    private(set) PhysicalPath $physicalPath { get => $this->physicalPath ??= $this->indexPage?->physicalPath ?? ''; }
    private(set) DateTimeImmutable $publishDate { get => $this->publishDate ??= $this->indexPage?->publishDate; }
    private(set) DateTimeImmutable $lastModifiedDate {
        get => $this->lastModifiedDate
            ??= $this->indexPage?->lastModifiedDate
            ?? new DateTimeImmutable('@' . $this->parsedFolder->mtime);
    }

    public function variants(): array
    {
        return $this->indexPage?->variants() ?? [];
    }

    public function variant(string $ext): ?Page
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
}
