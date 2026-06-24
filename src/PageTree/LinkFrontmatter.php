<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\Carica\HttpStatus;
use Crell\Serde\Attributes\Field;
use DateTimeImmutable;

class LinkFrontmatter implements ParsedFrontmatter
{
    #[Field(exclude: true)]
    public string $code {
        get => $this->other['code'] ?? HttpStatus::PermanentRedirect->value;
    }

    #[Field(exclude: true)]
    public string $location {
        get => $this->other['location'];
    }

    /**
     * @param list<string> $tags
     * @param array<string, string|int|float> $other
     */
    public function __construct(
        public ?string $title = null,
        public ?string $summary = null,
        public array $tags = [],
        public ?string $slug = null,
        public bool $hidden = true,
        public ?bool $routable = null,
        public ?DateTimeImmutable $publishDate = null,
        public ?DateTimeImmutable $lastModifiedDate = null,
        #[Field(flatten: true)]
        public array $other = [],
    ) {}
}
