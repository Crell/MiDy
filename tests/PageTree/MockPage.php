<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use DateTimeImmutable;

class MockPage implements Page
{
    private array $values;

    public string $title {
        get => $this->values[__PROPERTY__] ?? '';
    }
    public string $summary {
        get => $this->values[__PROPERTY__] ?? '';
    }
    public array $tags {
        get => $this->values[__PROPERTY__] ?? [];
    }
    public bool $hidden {
        get => $this->values[__PROPERTY__] ?? false;
    }
    public bool $routable {
        get => $this->values[__PROPERTY__] ?? true;
    }
    public DateTimeImmutable $publishDate {
        get => $this->values[__PROPERTY__] ?? new DateTimeImmutable();
    }
    public DateTimeImmutable $lastModifiedDate {
        get => $this->values[__PROPERTY__] ?? new DateTimeImmutable();
    }
    public array $other {
        get => $this->values[__PROPERTY__] ?? [];
    }
    public string $path {
        get => $this->values[__PROPERTY__] ?? '';
    }
    public string $physicalPath {
        get => $this->values[__PROPERTY__] ?? '';
    }
    public string $folder {
        get => $this->values[__PROPERTY__] ?? '';
    }

    public function __construct(...$values)
    {
        $this->values = $values;
    }

    /**
     * @inheritDoc
     */
    public function variants(): array
    {
        // TODO: Implement variants() method.
    }

    public function variant(string $ext): ?Page
    {
        // TODO: Implement variant() method.
    }

    public function getTrailingPath(string $fullPath): array
    {
        // TODO: Implement getTrailingPath() method.
    }
}