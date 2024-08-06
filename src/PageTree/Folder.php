<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Traversable;

use function Crell\fp\afilterWithKeys;

class Folder extends Page implements \Countable, \IteratorAggregate
{
    /**
     * @param array<string, Page> $pages
     * @param array<string, RouteProvider> $providers
     */
    public function __construct(
        string $filename,
        string $urlPath,
        readonly private array $providers = [],
        string $title = 'Home',
    ) {
        parent::__construct($filename, $urlPath, $title);
    }

    public function getIterator(): \Traversable
    {
        return $this->children();
    }

    public function count(): int
    {
        return count($this->children());
    }

    public function child(string $name): Page|Folder|null
    {
        return $this->children()->get($name);
    }

    public function children(): PageList
    {
        $relevantProviders = $this->findRelevantProviders();

        $children = [];
        foreach ($relevantProviders as $provider) {
            $providerChildren = $provider->children($this->urlPath);
            $children += $providerChildren;
        }
        foreach ($children as $name => $filePath) {
            $childUrlPath = rtrim($this->urlPath, '/') . "/$name";
            $children[$name] = is_dir($filePath)
                ? new Folder($filePath,  $childUrlPath, $relevantProviders, ucfirst($name))
                : new Page($filePath, $childUrlPath, ucfirst($name));
        }

        // Sorting goes here, eventually.

        return new PageList($children);
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
