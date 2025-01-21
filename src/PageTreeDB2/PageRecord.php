<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

/**
 * @todo For now this is being used for both read and write. That may or may not be ideal.
 */
class PageRecord
{
    // @todo More robust than this.
    public int $title {
        get => $this->values(__PROPERTY__)[0];
    }

    public int $order {
        get => \max($this->values(__PROPERTY__));
    }

    public bool $hidden {
        get => array_all($this->values(__PROPERTY__), static fn($x): bool => (bool)$x);
    }

    public bool $routable {
        get => array_any($this->values(__PROPERTY__), static fn($x): bool => (bool)$x);
    }

    public bool $isFolder {
        get => array_any($this->values(__PROPERTY__), static fn($x): bool => (bool)$x);
    }

    public \DateTimeImmutable $publishDate {
        get => \max($this->values(__PROPERTY__));
    }

    public \DateTimeImmutable $lastModifiedDate {
        get => \max($this->values(__PROPERTY__));
    }

    public string $pathName {
        get => substr($this->logicalPath, strrpos($this->logicalPath, '/') + 1);
    }

    public array $tags {
        get => array_values(array_unique(array_merge(...array_column(array_column($this->files, 'frontmatter'), 'tags'))));
    }

    /**
     * @param string $logicalPath
     * @param string $folder
     * @param array<ParsedFile> $files
     */
    public function __construct(
        public string $logicalPath,
        public string $folder,
        public array $files,
    ) {}

    private function values(string $property): array
    {
        return array_column($this->files, $property);
    }
}
