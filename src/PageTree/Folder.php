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
        string $urlPath,
        readonly private array $providers = [],
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
        $relevantProviders = $this->findRelevantProviders();

        $children = [];
        /** @var RouteProvider $provider */
        foreach ($relevantProviders as $providerPrefix =>  $provider) {
            $providerChildren = $provider->children($this->urlPath);
            foreach ($providerChildren as $name => $filePath) {
                // Because the file name is used as an array key,
                // if it is numeric, PHP will helpfully coerce it to an int
                // for us.  But that breaks using it as a string, so we have
                // to undo that.  Silly PHP.
                $name = (string)$name;
                $childUrlPath = rtrim($this->urlPath, '/') . "/$name";
                if (is_dir($filePath)) {
                    $childProviders = $this->findChildProviders($childUrlPath) ?: [$providerPrefix => $provider];
                    $children[$name] = new Folder($childUrlPath, $childProviders, ucfirst($name));
                } else {
                    $children[$name] = new Page($childUrlPath, ucfirst($name));
                }
            }
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

    /**
     * Returns just those providers that are relevant for the specified sub-path.
     */
    private function findChildProviders(string $path): iterable
    {
        $ret = [];
        foreach ($this->providers as $prefix => $provider) {
            if (str_starts_with($prefix, $path)) {
                $ret[$prefix] = $provider;
            }
        }
        return $ret;
    }
}
