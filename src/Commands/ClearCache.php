<?php

declare(strict_types=1);

namespace Crell\MiDy\Commands;

use DI\Attribute\Inject;
use function Crell\MiDy\rmdir_contents;

/**
 * Clears all caches.
 */
readonly class ClearCache
{
    public function __construct(
        #[Inject('paths.cache')]
        private string $cachePath,
    ) {}

    public function run(): void
    {
        print "Removing cache files.\n";
        rmdir_contents($this->cachePath);
    }


}
