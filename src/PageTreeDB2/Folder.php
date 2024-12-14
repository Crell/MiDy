<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Traversable;

class Folder implements \Countable, \IteratorAggregate, PageSet
{
    public private(set) string $logicalPath {
        get => $this->logicalPath ??= $this->parsedFolder->logicalPath;
    }

    public private(set) PageSet $children {
        get => $this->children ??= new BasicPageSet($this->pageTree->pages($this->logicalPath));
    }

    public function __construct(
        private readonly ParsedFolder $parsedFolder,
        private readonly PageTree $pageTree,
    ) {}

    public function count(): int
    {
        return count($this->children);
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->children);
    }

    public function limit(int $count): PageSet
    {
        return new BasicPageSet(new \LimitIterator(new \IteratorIterator($this->children), $count));
    }

    public function all(): PageSet
    {
        return $this->children;
    }

    public function filter(\Closure $filter): PageSet
    {
        return new BasicPageSet(iterator_to_array(new \CallbackFilterIterator(new \IteratorIterator($this->all()), $filter)));
    }

    public function get(string $name): ?Page
    {
        return $this->children->get($name);
    }

    public function __debugInfo(): ?array
    {
        return [
            'logicalPath' => $this->logicalPath,
            'physicalPath' => $this->parsedFolder->physicalPath,
            'mtime' => $this->parsedFolder->mtime,
            'children count' => count($this->children),
        ];
    }
}
