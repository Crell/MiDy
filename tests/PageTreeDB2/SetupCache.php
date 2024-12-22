<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use PHPUnit\Framework\Attributes\Before;

trait SetupCache
{
    private PageCacheDB $cache;

    private \PDO $db;

    #[Before(priority: 15)]
    public function setupCache(): PageCacheDB
    {
        $this->cache ??= new PageCacheDB($this->db);
        $this->cache->reinitialize();
        return $this->cache;
    }

    #[Before(priority: 20)]
    public function setupMockDb(): \PDO
    {
        return $this->db ??= new \PDO('sqlite::memory:');
    }

    private function dumpFilesTable(): void
    {
        var_dump($this->db->query("SELECT logicalPath, physicalPath, folder FROM file")->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function dumpTagsTable(): void
    {
        var_dump($this->db->query("SELECT * FROM file_tag")->fetchAll(\PDO::FETCH_ASSOC));
    }
}
