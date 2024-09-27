<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\FolderParser;

use Crell\MiDy\PageTree\FileInterpreter\FileInterpreterError;
use Crell\MiDy\PageTree\Folder;
use Crell\MiDy\PageTree\OldFolder;
use Crell\MiDy\PageTree\PageFile;
use Crell\MiDy\PageTree\SortOrder;
use Traversable;

/**
 * @internal
 */
class FolderParserDatalist implements \IteratorAggregate
{
    private array $toBuild = [];

    private bool $sorted = false;

    public function __construct(private readonly SortOrder $sortOrder) {}

    public function addPageFile(string $variant, string $basename, int $order, PageFile|FileInterpreterError $routeFile): void
    {
        if ($routeFile === FileInterpreterError::FileNotSupported) {
            // For now, just ignore unsupported file types.
            // @todo This should probably get logged, at least.
            return;
        }

        $this->toBuild[$routeFile->logicalPath] ??= [
            'type' => 'page',
            'variants' => [],
            'order' => $order,
            'hidden' => false,
            'fileName' => pathinfo($routeFile->logicalPath, PATHINFO_FILENAME),
        ];

        if ($basename === Folder::IndexPageName) {
            $this->toBuild[$routeFile->logicalPath]['hidden'] = true;
        }

        $this->sorted = false;

        $this->toBuild[$routeFile->logicalPath]['variants'][$variant] = $routeFile;
    }

    public function addFolder(string $basename, int $order, \SplFileInfo $file, array $controlData, string $childPhysicalPath, string $childLogicalPath): void
    {
        $this->sorted = false;

        $this->toBuild[$childLogicalPath] ??= [
            'type' => 'folder',
            'physicalPath' => $childPhysicalPath,
            'order' => $order,
            'fileName' => $basename,
            'hidden' => $controlData['hidden'],
        ];

        $this->toBuild[$childLogicalPath]['data'] = $file;
    }

    public function getIterator(): Traversable
    {
        $this->sort();

        return new \ArrayIterator($this->toBuild);
    }

    private function sort(): void
    {
        if ($this->sorted) {
            return;
        }

        $comparator = match ($this->sortOrder) {
            SortOrder::Asc => $this->sortAsc(...),
            SortOrder::Desc => $this->sortDesc(...),
        };
        uasort($this->toBuild, $comparator);

        $this->sorted = true;
    }

    private function sortAsc(array $a, array $b): int
    {
        return [$a['order']] <=> [$b['order']];
    }

    private function sortDesc(array $a, array $b): int
    {
        return [$b['order']] <=> [$a['order']];
    }
}
