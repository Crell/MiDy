<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use PHPUnit\Framework\Attributes\Before;

trait SetupDB
{
    private \PDO $db;

    #[Before(priority: 20)]
    public function setupMockDb(): \PDO
    {
        return $this->db ??= new \PDO('sqlite::memory:');
    }
}
