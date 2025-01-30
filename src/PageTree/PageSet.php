<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

/**
 * A collection of logical pages, however derived.
 *
 * When traversing with foreach(), implementations SHOULD use a reasonable default
 * filtering (eg, only show non-hidden pages).
 */
interface PageSet extends \Countable, \Traversable
{
    /**
     * Returns a new PageSet, truncated to the specified length.
     */
    public function limit(int $limit): PageSet;

    /**
     * Retrieves a single item from the set, or null if it doesn't exist.
     *
     * The name MAY include an extension. If it does, it will only return a value
     * if that particular extension is present.  If not, it will return a value
     * if any extension is present.
     */
    public function get(string $name): ?Page;

    /**
     * Filters the PageSet down to just those items that match the filter callback.
     *
     * @param callable(Page $p): bool $filter
     */
    public function filter(\Closure $filter, int $pageSize = PageRepo::DefaultPageSize, int $pageNum = 1): Pagination;

    /**
     * Filters this page set to just those items that have at least one specified tag.
     */
    public function filterAnyTag(array $tags, int $pageSize = PageRepo::DefaultPageSize, int $pageNum = 1): Pagination;
}
