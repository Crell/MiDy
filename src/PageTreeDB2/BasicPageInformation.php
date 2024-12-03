<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

readonly class BasicPageInformation implements PageInformation
{
    public function __construct(
        public string $title = '',
        public string $summary = '',
        public array $tags = [],
        public ?string $slug = null,
        public bool $hidden = false,
    ) {}

    public function hasAnyTag(string ...$tags): bool
    {
        return (bool)array_intersect($this->tags, $tags);
    }

    public function hasAllTags(string ...$tags): bool
    {
        foreach ($tags as $tag) {
            if (!in_array($tag, $this->tags, true)) {
                return false;
            }
        }
        return true;
    }
}
