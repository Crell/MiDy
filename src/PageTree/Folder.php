<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class Folder extends Page implements \Countable, \IteratorAggregate
{
    private readonly PageList $children;

    /**
     * @param array<string, Page> $pages
     * @param array<string, RouteProvider> $providers
     */
    public function __construct(
        string $urlPath,
        private readonly ProviderMap $providers,
        string $title = 'Home',
    ) {
        parent::__construct($urlPath, $title);
    }

    public function getIterator(): \Traversable
    {
        return $this->children();
    }

    public function count(): int
    {
        return count($this->children());
    }

    public function type(): PageType
    {
        return PageType::Folder;
    }

    public function child(string $name): Page|Folder|null
    {
        return $this->children()->get($name);
    }

    public function children(): PageList
    {
        return $this->children ??= $this->computeChildren();
    }

    private function computeChildren(): PageList
    {
        return $this->find('/*');
    }

    public function find(string $pattern): PageList
    {
        $absolutePattern = rtrim($this->urlPath, '/') . '/' . ltrim($pattern, '/');

        return $this->providers
            ->findForPath($absolutePattern)
            ->find($absolutePattern, $this) ?? new PageList();
    }

    public function findChildProviders($childUrlPath): ProviderMap
    {
        return $this->providers->filterForGlob($childUrlPath);
    }
}
