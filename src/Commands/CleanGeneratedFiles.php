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
        $this->clearCache();
        $this->clearPregenerated();

        $this->clearEmptyDirectories($this->cachePath);
        $this->clearEmptyDirectories($this->publicPath);
    }

    /**
     * This is a fugly hack, because PHP's filesystem API is a fugly hack.
     *
     * This will likely fail with multiple nested directories.
     *
     * @todo Fix this mess.
     *
     * @param string $directory
     *   The directory in which to remove empty directories.
     * @return void
     */
    private function clearEmptyDirectories(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        $dirs = new \CallbackFilterIterator($iterator, static fn (\SplFileInfo $f) => $f->isDir());
        $dirs = new \CallbackFilterIterator($dirs, static fn(\SplFileInfo $f) => $f->getFilename() !== '..');
        $dirs = new \CallbackFilterIterator($dirs, static fn(\SplFileInfo $f) => $f->getRealPath() !== $directory);

        /** @var \SplFileInfo $dir */
        foreach ($dirs as $dir) {
            rmdir($dir->getRealPath());
        }
    }

    private function clearCache(): void
    {
        print "Removing cache files.\n";

        $flags = \FilesystemIterator::KEY_AS_PATHNAME|\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS;
        $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->cachePath, flags: $flags));

        /** @var \SplFileInfo $file */
        foreach ($iter as $file) {
            unlink($file->getRealPath());
        }
    }

    private function clearPregenerated(): void
    {
        print "Removing generated static files.\n";

        $filter = static fn(\SplFileInfo $f) => $f->getBasename() !== 'index.php';

        $flags = \FilesystemIterator::KEY_AS_PATHNAME|\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS;
        $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->publicPath, flags: $flags));
        $files = new \CallbackFilterIterator($iter, $filter);

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            unlink($file->getRealPath());
        }
    }
}
