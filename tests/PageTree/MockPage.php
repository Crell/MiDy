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
    public PhysicalPath $physicalPath {
        get => $this->values[__PROPERTY__] ?? throw new \InvalidArgumentException('No physical path defined.');
    }
    public string $folder {
        get => $this->values[__PROPERTY__] ?? '';
    }

    public function __construct(mixed ...$values)
    {
        $this->values = $values;
    }

    public function hasAnyTag(string ...$tags): bool
    {
        return count(array_intersect($tags, $this->tags)) > 0;
    }

    /**
     * @inheritDoc
     */
    public function variants(): never
    {
        throw new \Exception('Not implemented.');
    }

    public function variant(string $ext): never
    {
        throw new \Exception('Not implemented.');
    }

    public function getTrailingPath(string $fullPath): never
    {
        throw new \Exception('Not implemented.');
    }
}