<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Model;

use Crell\Serde\Attributes\Field;
use DateTimeImmutable;

class BasicParsedFrontmatter implements ParsedFrontmatter
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
        #[Field(flatten: true)]
        public array $other = [],
    ) {}
}
