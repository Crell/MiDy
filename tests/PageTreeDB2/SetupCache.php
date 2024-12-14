<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use PHPUnit\Framework\Attributes\Before;

trait SetupCache
{
    private PageCacheDB $cache;

    private \PDO $db;

    #[Before]
    public function setupCache(): PageCacheDB
    {
        $this->cache ??= new PageCacheDB($this->db);
        $this->cache->reinitialize();
        return $this->cache;
    }

    #[Before]
    public function setupMockDb(): \PDO
    {
        return $this->db ??= new \PDO('sqlite::memory:');
    }

    private function dumpFilesTable(): void
    {
        var_dump($this->db->query("SELECT logicalPath, physicalPath, folder FROM file")->fetchAll(\PDO::FETCH_ASSOC));
    }
}
