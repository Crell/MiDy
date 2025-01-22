<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2\Parser;

use Crell\MiDy\PageTree\SortOrder;
use Crell\MiDy\PageTreeDB2\PageRecord;
use Crell\MiDy\PageTreeDB2\ParsedFile;

/**
 * @internal
 */
class ParserFileList implements \IteratorAggregate
{
    /** @var array<string, array<ParsedFile>>  */
    private array $files = [];

    private \Closure $comparator {
        get => $this->comparator ??= match ($this->sortOrder) {
            SortOrder::Asc => $this->sortAsc(...),
            SortOrder::Desc => $this->sortDesc(...),
        };
    }

    public function __construct(
        private readonly SortOrder $sortOrder,
    ) {}

    public function addParsedFile(ParsedFile $file): void
    {
        // The easiest way to default the order to reversed is just this.
        if ($this->sortOrder === SortOrder::Desc) {
            $file->order *= -1;
        }
        $this->files[$file->logicalPath][] = $file;
    }

    public function getIterator(): \Generator
    {
        foreach ($this->files as $path => $files) {
            // We only need to sort the files within the Page, as we cannot query on that order later.
            usort($files, $this->comparator);
            yield new PageRecord($path, $files[0]->folder, $files);
        }
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
