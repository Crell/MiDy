<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Attributes;

use Crell\MiDy\PageTree\MiDyFrontMatter;

#[\Attribute]
readonly class PageRoute implements MiDyFrontMatter
{
    public function __construct(
        public string $title = '',
        public string $summary = '',
        public array $tags = [],
        public ?string $slug = null,
        public bool $hidden = false,
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

    public function hidden(): bool
    {
        return $this->hidden;
    }
}
