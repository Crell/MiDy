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
}
