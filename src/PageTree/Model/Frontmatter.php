<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Model;

use DateTimeImmutable;

/**
 * Almost the same as ParsedFrontmatter, but all fields are required to exist.
 *
 * Also, no slug, as that's not needed for reading.
 */
interface Frontmatter
{
    public string $title { get; }
    public string $summary { get; }
    public array $tags { get; }
    public bool $hidden { get; }
    public bool $routable { get; }
    public DateTimeImmutable $publishDate { get; }
    public DateTimeImmutable $lastModifiedDate { get; }
    public array $other { get; }
}
