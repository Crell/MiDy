<?php

declare(strict_types=1);

namespace Crell\MiDy\Commands;

use Crell\MiDy\PageTree\PageTree;

readonly class Reindex
{
    public function __construct(
        private PageTree $pageTree,
    ) {}

    public function run(): void
    {
//        print "Reindexing site...\n";
        $this->pageTree->reindexAll();
//        print "Done.\n";
    }
}
