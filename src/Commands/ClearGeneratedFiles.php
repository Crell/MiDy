<?php

declare(strict_types=1);

namespace Crell\MiDy\Commands;

use DI\Attribute\Inject;
use function Crell\MiDy\rmdir_contents;

/**
 * Clears all caches, deletes all auto-copied generated pages.
 */
readonly class ClearGeneratedFiles
{
    public function __construct(
        #[Inject('paths.public')]
        private string $publicPath,
    ) {}

    public function run(): void
    {
        print "Removing generated static files.\n";
        rmdir_contents($this->publicPath, fn (\SplFileInfo $f) => $f->getRealPath() !== $this->publicPath . '/index.php');
    }
}
