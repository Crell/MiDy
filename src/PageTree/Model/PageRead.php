<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Model;

use DateTimeImmutable;

class PageRead
{
    protected FileInPage $activeFile { get => $this->activeFile ??= array_values($this->files)[0]; }

    public array $other {
        get => $this->activeFile->other;
    }

    /**
     * @param array<string> $tags
     * @param array<string, FileInPage> $files
     */
    public function __construct(
        public string $logicalPath,
        public string $folder,
        public string $title,
        public int $order,
        public bool $hidden,
        public bool $routable,
        public bool $isFolder,
        public DateTimeImmutable $publishDate,
        public DateTimeImmutable $lastModifiedDate,
        public array $tags,
        public array $files,
    ) {}

    // Variant stuff here.
}
