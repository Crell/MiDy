<?php

declare(strict_types=1);

namespace Crell\MiDy\Commands;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTree\Model\FileInPage;
use Crell\MiDy\PageTree\PageRepo;
use Crell\MiDy\PageTree\PageTree;
use DI\Attribute\Inject;

use function Crell\fp\itfilter;
use function Crell\fp\itmap;
use function Crell\fp\pipe;
use function Crell\MiDy\ensure_dir;

readonly class StaticFilePregenerator
{
    public function __construct(
        private PageTree $tree,
        private PageRepo $cache,
        private StaticRoutes $staticRoutes,
        #[Inject('paths.public')]
        private string $publicPath,
    ) {}

    public function run(): void
    {
        // First, ensure the index is fully up to date.
        $this->tree->reindexAll();

        // Now get every single page in the index, and copy it to the target path.
        pipe($this->cache->allFiles(),
            itfilter($this->filterStatic(...)),
            itmap($this->copyFile(...))
        );
    }

    private function copyFile(FileInPage $file): void
    {
        $dest = $this->publicPath . $file->physicalPath . '.' . $file->ext;
        ensure_dir(pathinfo($dest, PATHINFO_DIRNAME));
        copy($file->physicalPath, $dest);
    }

    private function filterStatic(FileInPage $file): bool
    {
        return array_key_exists($file->ext, $this->staticRoutes->allowedExtensions);
    }
}
