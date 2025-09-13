<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use PHPUnit\Framework\Attributes\Before;

trait SetupRepo
{
    use SetupDB;

    private YiiDbPageCache $repo;

    #[Before(15)]
    public function setupRepo(): void
    {
        $this->repo ??= new YiiDbPageCache(conn: $this->conn);
        $this->repo->reinitialize();
    }

    private function dumpPageTable(): void
    {
        var_dump($this->conn->createCommand("SELECT * FROM page")->queryAll());
    }
}
