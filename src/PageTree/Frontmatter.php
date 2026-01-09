<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

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

    /**
     * @var list<string>
     */
    public array $tags { get; }
    public bool $hidden { get; }
    public bool $routable { get; }
    public DateTimeImmutable $publishDate { get; }
    public DateTimeImmutable $lastModifiedDate { get; }

    /**
     * @var array<string, string|int|float|array<string, mixed>>
     */
    public array $other { get; }

    public function hasAnyTag(string ...$tags): bool;
}
