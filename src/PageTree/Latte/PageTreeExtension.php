<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Latte;

use Crell\MiDy\PageTree\Folder;
use Crell\MiDy\PageTree\Page;
use Crell\MiDy\PageTree\PageCache;
use Crell\MiDy\PageTree\PageTree;
use Crell\MiDy\PageTree\Pagination;
use Crell\MiDy\PageTree\Router\HttpQuery;
use Latte\Extension;

class PageTreeExtension extends Extension
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly PageTree $pageTree,
    ) {}

    public function getFunctions(): array
    {
        return [
            'pageUrl' => $this->pageUrl(...),
            'atomId' => $this->atomId(...),
            'pageQuery' => $this->pageQuery(...),
            'folder' => $this->folder(...),
            'page' => $this->page(...),
        ];
    }

    /**
     * @param Page $page
     * @param array<string, string|int|float|null>|HttpQuery $query
     * @return string
     */
    public function pageUrl(Page $page, array|HttpQuery $query = []): string
    {
        if (is_array($query)) {
            $query = new HttpQuery($query);
        }

        return sprintf("%s%s%s", rtrim($this->baseUrl, '/'), $page->path, $query);
    }

    /**
     * Create an Atom ID for the specified page.
     *
     * The algorithm here is taken from
     * @link https://web.archive.org/web/20110514113830/http://diveintomark.org/archives/2004/05/28/howto-atom-id
     * @link https://datatracker.ietf.org/doc/html/rfc4151
     */
    public function atomId(Page $page): string
    {
        $url = $this->pageUrl($page);
        $parts = parse_url($url);

        if (isset($parts['fragment'])) {
            $parts['path'] .= '/' . $parts['fragment'];
        }

        $date = $page->publishDate->format('Y-m-d');

        return sprintf('tag:%s,%s:%s', $parts['host'], $date, $parts['path']);
    }

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
     */
    public function pageQuery(
        ?string $folder = null,
        bool $deep = false,
        bool $includeHidden = false,
        bool $routableOnly = true,
        array $anyTag = [],
        ?\DateTimeInterface $publishedBefore = new \DateTimeImmutable(),
        array $orderBy = [],
        int $pageSize = PageCache::DefaultPageSize,
        int $pageNum = 1,
        array $exclude = [],
    ): Pagination {
        return $this->pageTree->queryPages(
            folder: $folder,
            deep: $deep,
            includeHidden: $includeHidden,
            routableOnly: $routableOnly,
            anyTag: $anyTag,
            publishedBefore: $publishedBefore,
            orderBy: $orderBy,
            pageSize: $pageSize,
            pageNum: $pageNum,
            exclude: $exclude,
        );
    }

    public function folder(string $path): ?Folder
    {
        return $this->pageTree->folder($path);
    }

    public function page(string $path): ?Page
    {
        return $this->pageTree->page($path);
    }
}
