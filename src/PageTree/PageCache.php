<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

interface PageCache
{
    public const int DefaultPageSize = 10;

    /**
     * Recreate all tables.
     */
    public function reinitialize(): void;

    public function writeFolder(ParsedFolder $folder): void;

    public function readFolder(LogicalPath $logicalPath): ?ParsedFolder;

    /**
     * This will also delete records for any files in this folder.
     */
    public function deleteFolder(LogicalPath $logicalPath): void;

    /**
     * @return array<ParsedFolder>
     */
    public function childFolders(LogicalPath $parentLogicalPath): array;

    public function writePage(PageData $page): void;

    public function readPage(LogicalPath $path): ?PageRecord;

    /**
     * @param list<string> $anyTag
     *   A list of tags for which to search.  A page will match if it has at least
     *   one of these.
     * @param array<string, int> $orderBy
     *   An associative array of properties to sort by. The key is the field name,
     *   the value is either SORT_ASC or SORT_DESC, as desired. Regardless of what
     *   is provided, the sort list will be appended with: order, title, path, to
     *   ensure queries are always deterministic.
     * @param string[] $exclude
     *   An array of paths to ignore in the query results. This is mainly useful
     *   for excluding the current page from listing pages other than an index page.
     *
     * @todo publishedAfter,
     *      titleContains
     */
    public function queryPages(
        string|LogicalPath|null $folder = null,
        bool $deep = false,
        bool $includeHidden = false,
        bool $routableOnly = true,
        array $anyTag = [],
        ?\DateTimeInterface $publishedBefore = new \DateTimeImmutable(),
        array $orderBy = [],
        int $limit = self::DefaultPageSize,
        int $offset = 0,
        array $exclude = [],
    ): QueryResult;

    /**
     * Returns a list of all paths that exist in the system.
     *
     * This is for the pre-generator logic.  Don't use it otherwise.
     *
     * @return iterable<string>
     */
    public function allPaths(): iterable;

    /**
     * Returns a list of all files that exist in the system.
     *
     * This is for the pre-generator logic.  Don't use it otherwise.
     *
     * @return iterable<File>
     */
    public function allFiles(): iterable;

    public function inTransaction(\Closure $closure): mixed;
}