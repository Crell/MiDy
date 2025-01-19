<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\ConsoleLogger;
use PHPUnit\Framework\Attributes\Before;

trait SetupRepo
{
    use SetupDB;

    private PageRepo $repo;

    #[Before(priority: 15)]
    public function setupRepo(): void
    {
        $this->repo ??= new PageRepo(conn: $this->yiiConn);
        $this->repo->reinitialize();
    }

    private function dumpPageTable(): void
    {
        var_dump($this->db->query("SELECT * FROM page")->fetchAll(\PDO::FETCH_ASSOC));
    }
}
