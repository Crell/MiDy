<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use function Crell\fp\pipe;

/**
 * A page to save to the database.
 *
 * This is the "write model."
 */
class PageData
{
    // @todo More robust than this.
    public string $title {
        get => $this->values(__PROPERTY__)[0];
    }
    public string $summary {
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
        get => pipe(
            array_merge(...$this->values('tags')),
            array_unique(...),
            array_values(...),
        );
    }

    public string $folder {
        get => current($this->parsedFiles)->folder;
    }

    public array $files {
        get => array_map(static fn(ParsedFile $f) => $f->toFile(), $this->parsedFiles);
    }

    /**
     * @param string $logicalPath
     * @param string $folder
     * @param array<string, ParsedFile> $parsedFiles
     */
    public function __construct(
        public string $logicalPath,
        private array $parsedFiles,
    ) {}

    private function values(string $property): array
    {
        return array_column($this->parsedFiles, $property);
    }
}
