<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use PHPUnit\Framework\Attributes\Before;

trait SetupRepo
{
    use SetupDoctrine;

    private PageCache $repo;

    #[Before(15)]
    public function setupRepo(): void
    {
        $this->repo ??= new DoctrinePageCache(conn: $this->conn);
        $this->repo->reinitialize();
    }
}
