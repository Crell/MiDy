<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Traversable;

use Webmozart\Glob\Glob;

use function Crell\fp\afilterWithKeys;

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
        $relevantProviders = $this->findRelevantProviders();

        $children = [];
        /** @var RouteProvider $provider */
        foreach ($relevantProviders as $providerPrefix =>  $provider) {
            $providerChildren = $provider->children($this->urlPath);
            $children += $this->instantiateFindResults($providerPrefix, $providerChildren, $provider);
        }

        // Sorting goes here, eventually.

        return new PageList($children);
    }

    public function find(string $pattern): PageList
    {
        $absolutePattern = rtrim($this->urlPath, '/') . '/' . ltrim($pattern, '/');

        $activeProvider = $this->providers->findForPath($absolutePattern);

        $found = $activeProvider->find($absolutePattern) ?? [];

        $children = [];
        foreach ($found as $name => $filePath) {
            // Because the file name is used as an array key,
            // if it is numeric, PHP will helpfully coerce it to an int
            // for us.  But that breaks using it as a string, so we have
            // to undo that.  Silly PHP.
            $name = (string)$name;
            $childUrlPath = rtrim($this->urlPath, '/') . "/$name";
            if (is_dir($filePath)) {
                $childProviders = $this->providers->filterForGlob($childUrlPath);
                $children[$name] = new Folder($childUrlPath, $childProviders, ucfirst($name));
            } else {
                $children[$name] = new Page($childUrlPath, ucfirst($name));
            }
        }
        return new PageList($children);
    }

    private function instantiateFindResults(string $providerPrefix, array $providerChildren, RouteProvider $provider): array
    {
        $children = [];
        foreach ($providerChildren as $name => $filePath) {
            // Because the file name is used as an array key,
            // if it is numeric, PHP will helpfully coerce it to an int
            // for us.  But that breaks using it as a string, so we have
            // to undo that.  Silly PHP.
            $name = (string)$name;
            $childUrlPath = rtrim($this->urlPath, '/') . "/$name";
            if (is_dir($filePath)) {
                $childProviders = $this->providers->filterForGlob($childUrlPath);
                $children[$name] = new Folder($childUrlPath, $childProviders, ucfirst($name));
            } else {
                $children[$name] = new Page($childUrlPath, ucfirst($name));
            }
        }
        return $children;
    }

    private function findRelevantProviders(): iterable
    {
        return afilterWithKeys($this->relevantProvider(...))($this->providers);
    }

    private function relevantProvider(RouteProvider $provider, string $prefix): bool
    {
        return str_starts_with($this->urlPath, $prefix);
    }
}
