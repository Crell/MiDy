<?php

declare(strict_types=1);

namespace Crell\MiDy\Commands;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTree\Page;
use Crell\MiDy\PageTree\PageFile;
use Crell\MiDy\PageTree\RootFolder;
use DI\Attribute\Inject;

use function Crell\MiDy\ensure_dir;

readonly class StaticFilePregenerator
{
    public function __construct(
        private RootFolder $root,
        private StaticRoutes $staticRoutes,
        #[Inject('paths.public')]
        private string $publicPath,
    ) {}

    public function run(): void
    {
        $files = new \CallbackFilterIterator($this->pageFileIterator(), $this->filterStatic(...));

        /** @var PageFile $file */
        foreach ($files as $file) {
            $dest = $this->publicPath . $file->logicalPath . '.' . $file->ext;
            ensure_dir(pathinfo($dest, PATHINFO_DIRNAME));
            copy($file->physicalPath, $dest);
        }
    }

    private function pageFileIterator(): \Generator
    {
        /** @var Page $page */
        foreach ($this->root->descendants(false) as $page) {
            yield from $page->variants();
        }
    }

    private function filterStatic(PageFile $p): bool
    {
        return array_key_exists($p->ext, $this->staticRoutes->allowedExtensions);
    }
}
