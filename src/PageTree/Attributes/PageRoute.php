<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Attributes;

use Crell\MiDy\PageTree\Model\ParsedFrontmatter;
use Crell\Serde\Attributes\Field;
use DateTimeImmutable;

#[\Attribute]
readonly class PageRoute implements ParsedFrontmatter
{
    public function __construct(
        public string $title = '',
        public string $summary = '',
        public array $tags = [],
        public ?string $slug = null,
        public bool $hidden = false,
        public bool $routable = true,
        public ?DateTimeImmutable $publishDate = null,
        public ?DateTimeImmutable $lastModifiedDate = null,
        public readonly string $template = '',
        #[Field(flatten: true)]
        public array $other = [],
    ) {}

    public function hasAnyTag(string ...$tags): bool
    {
        return (bool)array_intersect($this->tags, $tags);
    }
}
