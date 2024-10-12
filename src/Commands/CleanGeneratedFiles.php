<?php

declare(strict_types=1);

namespace Crell\MiDy\Commands;

use DI\Attribute\Inject;

/**
 * Clears all caches, deletes all auto-copied generated pages.
 */
readonly class CleanGeneratedFiles
{
    public function __construct(
        #[Inject('paths.public')]
        private string $publicPath,
        #[Inject('paths.cache')]
        private string $cachePath,
    ) {}

    public function run(): void
    {
        print "Removing cache files.\n";
        $this->rmdirContents($this->cachePath);

        print "Removing generated static files.\n";
        $this->rmdirContents($this->publicPath, fn (\SplFileInfo $f) => $f->getRealPath() !== $this->publicPath . '/index.php');
    }

    /**
     * Recursively removes all files in a directory, but not the directory itself.
     *
     * @param string $dir
     *   The directory to dlear.
     * @param \Closure|null $filter
     *   If specified, only files/directories that evaulate to true when passed to this
     *   callback will be removed.
     */
    function rmdirContents(string $dir, ?\Closure $filter = null): void {
        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it,
            \RecursiveIteratorIterator::CHILD_FIRST);
        if ($filter) {
            $files = new \CallbackFilterIterator($files, $filter);
        }
        /** @var \SplFileInfo $file */
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
    }
}
