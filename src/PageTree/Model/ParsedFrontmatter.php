<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Model;

use Crell\Serde\Attributes\Field;
use DateTimeImmutable;

class ParsedFrontmatter
{
    public function __construct(
        public string $title = '',
        public string $summary = '',
        public array $tags = [],
        public ?string $slug = null,
        public bool $hidden = false,
        public ?DateTimeImmutable $publishDate = null,
        public ?DateTimeImmutable $lastModifiedDate = null,
        #[Field(flatten: true)]
        public array $other = [],
    ) {}
}
