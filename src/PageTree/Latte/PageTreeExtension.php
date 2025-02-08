<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Latte;

use Crell\MiDy\PageTree\Page;
use Latte\Extension;

class PageTreeExtension extends Extension
{
    public function __construct(private readonly string $baseUrl) {}

    public function getFunctions(): array
    {
        return [
            'pageUrl' => $this->pageUrl(...),
            'atomId' => $this->atomId(...),
        ];
    }

    public function pageUrl(Page $page, array $query = []): string
    {
        $queryString = '';
        if ($query) {
            $queryString = '?' . http_build_query($query);
        }

        return sprintf("%s%s%s", rtrim($this->baseUrl, '/'), $page->path, $queryString);
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
}
