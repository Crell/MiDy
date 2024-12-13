<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Traversable;

class Folder implements \Countable, \IteratorAggregate
{
    public private(set) string $logicalPath {
        get => $this->logicalPath ??= $this->parsedFolder->logicalPath;
    }

    /**
     * @var array<string, Page>
     *
     * @todo eventually this becomes a PageSet.
     */
    public private(set) array $children {
        get => $this->children ??= $this->pageTree->pages($this->logicalPath);
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
