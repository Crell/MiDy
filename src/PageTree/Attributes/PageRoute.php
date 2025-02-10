<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Attributes;

use Crell\MiDy\PageTree\ParsedFrontmatter;
use Crell\Serde\Attributes\Field;
use DateTimeImmutable;

#[\Attribute]
readonly class PageRoute implements ParsedFrontmatter
{
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
