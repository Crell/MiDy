<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Attributes;

use Crell\MiDy\PageTree\ParsedFrontmatter;
use Crell\Serde\Attributes\Field;
use DateTimeImmutable;

#[\Attribute]
readonly class PageRoute implements ParsedFrontmatter
{
    /**
     * @param list<string> $tags
     * @param array<string, string|int|float|array<string, mixed>> $other
     */
    public function __construct(
        public ?string $title = null,
        public ?string $summary = null,
        public array $tags = [],
        public ?string $slug = null,
        public ?bool $hidden = null,
        public ?bool $routable = null,
        public ?DateTimeImmutable $publishDate = null,
        public ?DateTimeImmutable $lastModifiedDate = null,
        #[Field(flatten: true)]
        public array $other = [],
    ) {}
}
