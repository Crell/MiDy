<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

readonly class MiDyBasicFrontMatter implements MiDyFrontMatter
{
    public function __construct(
        public string $title = '',
        public string $summary = '',
        public array $tags = [],
        public ?string $slug = null,
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function summary(): string
    {
        return $this->summary;
    }

    public function tags(): array
    {
        return $this->tags;
    }

    public function slug(): ?string
    {
        return $this->slug;
    }
}
