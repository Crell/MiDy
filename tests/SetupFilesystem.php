<?php

declare(strict_types=1);

namespace Crell\MiDy;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\Before;

trait SetupFilesystem
{
    protected vfsStreamDirectory $vfs;

    /**
     * The path to where routable files are stored.
     *
     * Does not include a trailing /.
     */
    protected string $routesPath;

    #[Before]
    public function initFilesystem(): void
    {
        // This mess is because vfsstream doesn't let you create multiple streams
        // at the same time.  Which is dumb.
        $structure = [
            'cache' => [],
            'routes' => [],
        ];

        $this->vfs = vfsStream::setup('root', null, $structure);
        $this->routesPath = $this->vfs->getChild('routes')?->url();
    }
}
