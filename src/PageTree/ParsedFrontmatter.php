<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use DateTimeImmutable;

/**
 * Frontmatter of a file, as parsed from the file contents.
 *
 * Because a file can easily skip all frontmatter, these values
 * are all optional, and either have the "zero" value of their type
 * as a default, or null.
 *
 * This is the "write model."
 */
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
