<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use PHPUnit\Framework\Attributes\Before;

trait SetupDoctrine
{
    private Connection $conn;

    #[Before(20)]
    public function setupDb(): void
    {
        $dsnParser = new DsnParser();
        $connectionParams = $dsnParser
            ->parse('pdo-sqlite:///:memory:');
        $this->conn = DriverManager::getConnection($connectionParams);
    }
}
