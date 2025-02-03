<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Model;

use Crell\Serde\Attributes\Field;
use DateTimeImmutable;

interface ParsedFrontmatter
{
    public string $title { get; }
    public string $summary { get; }
    public array $tags { get; }
    public ?string $slug { get; }
    public bool $hidden { get; }
    public bool $routable { get; }
    public ?DateTimeImmutable $publishDate { get; }
    public ?DateTimeImmutable $lastModifiedDate { get; }
    public array $other { get; }
}
