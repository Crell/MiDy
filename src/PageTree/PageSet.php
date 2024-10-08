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
    public function limit(int $count): PageSet;

    /**
     * Returns a Pagination definition for this page set.
     *
     * @param int $pageSize
     *   The number of items per page.
     * @param int $pageNum
     *   Which page's data to show.  1-based, so page 1 is the first page.
     */
    public function paginate(int $pageSize, int $pageNum = 1): Pagination;

    /**
     * Returns all pages in this set, without any filtering at all.  Use with caution.
     *
     * @return iterable<string, Page>
     */
    public function all(): iterable;

    /**
     * Filters the PageSet down to just those items that match the filter callback.
     *
     * @param callable(Page $p): bool $filter
     * @return PageSet
     */
    public function filter(\Closure $filter): PageSet;

    /**
     * Retrieves a single item from the set, or null if it doesn't exist.
     *
     * The name MAY include an extension. If it does, it will only return a value
     * if that particular extension is present.  If not, it will return a value
     * if any extension is present.
     */
    public function get(string $name): ?Page;

    /**
     * Filters this page set to just those items that have at least one specified tag.
     */
    public function filterAnyTag(string ...$tags): PageSet;

    /**
     * Filters this page set to just those items that have all the specified tags.
     */
    public function filterAllTags(string ...$tags): PageSet;
}
