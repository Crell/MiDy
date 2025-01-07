<?php

declare(strict_types=1);

namespace Crell\MiDy\Commands;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTree\RootFolder;
use Crell\MiDy\PageTreeDB2\PageCacheDB;
use Crell\MiDy\PageTreeDB2\PageTree;
use Crell\MiDy\StackMiddlewareKernel;
use DI\Attribute\Inject;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;

use function Crell\MiDy\ensure_dir;

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
