<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Webmozart\Glob\Glob;

readonly class PathMap implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string, mixed> $paths
     */
    private readonly array $paths;

    /**
     * @param array<string, mixed> $paths
     */
    public function __construct(array $paths)
    {
        krsort($paths);
        $this->paths = $paths;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->paths);
    }

    public function count(): int
    {
        return count($this->paths);
    }

    /**
     * Returns the one single item relevant for the specified path.
     */
    public function findForPath(string $path): mixed
    {
        // Paths are ordered longest to shortest.
        // That means the first path that is a prefix of the current path
        // is the item to use.
        foreach ($this->paths as $prefix => $item) {
            if (str_starts_with($path, $prefix)) {
                return $item;
            }
        }

        // Should never happen unless there is no / entry.
        return null;
    }

    /**
     * Returns all items relevant to a given path glob pattern.
     *
     * Note: This uses the Webmozart\Glob's extended syntax.
     */
    public function filterForGlob(string $pattern): static
    {
        $pathToMatch = Glob::getStaticPrefix($pattern);
        if ($pathToMatch !== '/') {
            $pathToMatch = rtrim($pathToMatch,'/');
        }

        // This finds any entries that match a subdirectory of the pattern/pathToMatch.
        $newPaths = array_filter($this->paths, static fn (string $itemPath): bool => str_starts_with($itemPath, $pathToMatch), ARRAY_FILTER_USE_KEY);

        // This finds an entry that matches this path itself, but not its children.
        $newPaths[$pathToMatch] ??= $this->findForPath($pathToMatch);

        return new static($newPaths);
    }
}
