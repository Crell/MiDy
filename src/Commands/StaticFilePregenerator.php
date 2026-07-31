<?php

declare(strict_types=1);

namespace Crell\MiDy\Commands;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTree\File;
use Crell\MiDy\PageTree\PageCache;
use Crell\MiDy\PageTree\PageTree;
use DI\Attribute\Inject;
use function Crell\MiDy\ensure_dir;

readonly class StaticFilePregenerator
{
    public function __construct(
        private PageTree $tree,
        private PageCache $cache,
        private StaticRoutes $staticRoutes,
        #[Inject('paths.public')]
        private string $publicPath,
    ) {}

    public function run(): void
    {
        // First, ensure the index is fully up to date.
        $this->tree->reindexAll();

        $pages = $this->cache->allFiles();
        foreach ($pages as $logicalPath => $files) {
            foreach ($files as $file) {
                if (array_key_exists($file->ext, $this->staticRoutes->allowedExtensions)) {
                    $this->copyFile($file, $logicalPath);
                }
            }
        }
    }

    private function copyFile(File $file, string $logicalPath): void
    {
        $dest = $this->publicPath . $logicalPath. '.' . $file->ext;
        ensure_dir(pathinfo($dest, PATHINFO_DIRNAME));
        copy((string)$file->physicalPath, $dest);
    }
}
