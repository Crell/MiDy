<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Model;

use DateTimeImmutable;

/**
 * Frontmatter metadata of a Page.
 *
 * Very similar to ParsedFrontmatter, but all values are required to exist.
 *
 * This is the "read model."
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
