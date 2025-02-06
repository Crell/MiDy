<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Model;

use Crell\MiDy\PageTree\Page;
use DateTimeImmutable;

/**
 * A saved Page record from the database.
 *
 * This is the "read model."
 */
class PageRecord implements Page
{
    private File $activeFile { get => $this->activeFile ??= array_values($this->files)[0]; }

    public array $other {
        get => $this->activeFile->other;
    }

    public string $physicalPath {
        get => $this->activeFile->physicalPath;
    }

    public string $path { get => $this->logicalPath; }

    /**
     * @param array<string> $tags
     * @param array<string, File> $files
     */
    public function __construct(
        private(set) string $logicalPath,
        private(set) string $folder,
        private(set) string $title,
        private(set) string $summary,
        private(set) int $order,
        private(set) bool $hidden,
        private(set) bool $routable,
        private(set) bool $isFolder,
        private(set) DateTimeImmutable $publishDate,
        private(set) DateTimeImmutable $lastModifiedDate,
        private(set) array $tags,
        private(set) array $files,
    ) {}

    public function variants(): array
    {
        // We need to preserve keys, which array_map() doesn't do.
        $ret = [];
        foreach (array_keys($this->files) as $ext) {
            $ret[$ext] = $this->variant($ext);
        }
        return $ret;
    }

    public function variant(string $ext): ?PageRecord
    {
        if (!array_key_exists($ext, $this->files)) {
            return null;
        }

        $ret = clone($this);
        $ret->files = [$ext => $this->files[$ext]];
        return $ret;
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
