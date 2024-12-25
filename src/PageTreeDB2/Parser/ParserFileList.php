<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2\Parser;

use Crell\MiDy\PageTree\SortOrder;
use Crell\MiDy\PageTreeDB2\ParsedFile;
use Traversable;

/**
 * @internal
 *
 * @todo This can probably be more efficient by doing grouped ordering
 *   and just doing a ksort/krsort. Though we'd still need to modify
 *   all objects.
 */
class ParserFileList implements \IteratorAggregate
{
    /** @var array<ParsedFile>  */
    private array $files = [];

    private bool $sorted = false;

    public function __construct(private readonly SortOrder $sortOrder) {}

    public function addParsedFile(ParsedFile $file): void
    {
        $this->files[] = $file;
    }

    public function getIterator(): Traversable
    {
        $this->sort();

        return new \ArrayIterator($this->files);
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
        uasort($this->files, $comparator);

        // Now that they're in order, we need to go over them a second time to set the ordering values.
        foreach (array_values($this->files) as $index => $file) {
            $file->order = $index;
        }

        $this->sorted = true;
    }

    private function sortAsc(ParsedFile $a, ParsedFile $b): int
    {
        return [$a->order] <=> [$b->order];
    }

    private function sortDesc(ParsedFile $a, ParsedFile $b): int
    {
        return [$b->order] <=> [$a->order];
    }
}
