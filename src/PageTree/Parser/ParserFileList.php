<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\PageTree\Model\PageWrite;
use Crell\MiDy\PageTree\Model\ParsedFileInformation;
use Crell\MiDy\PageTree\SortOrder;

/**
 * @internal
 */
class ParserFileList implements \IteratorAggregate
{
    /** @var array<string, array<ParsedFileInformation>>  */
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

    public function addParsedFile(ParsedFileInformation $file): void
    {
        // The easiest way to default the order to reversed is just this.
        if ($this->sortOrder === SortOrder::Desc) {
            $file->order *= -1;
        }
        $this->files[$file->logicalPath][$file->ext] = $file;
    }

    public function getIterator(): \Generator
    {
        foreach ($this->files as $path => $files) {
            // We only need to sort the files within the Page, as we cannot query on that order later.
            uasort($files, $this->comparator);
            yield new PageWrite($path, $files);
        }
    }

    private function sortAsc(ParsedFileInformation $a, ParsedFileInformation $b): int
    {
        return [$a->order] <=> [$b->order];
    }

    private function sortDesc(ParsedFileInformation $a, ParsedFileInformation $b): int
    {
        return [$b->order] <=> [$a->order];
    }
}
