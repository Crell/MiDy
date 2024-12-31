<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\ConsoleLogger;
use PHPUnit\Framework\Attributes\Before;

trait SetupCache
{
    use SetupDB;

    private PageCacheDB $cache;

    #[Before(priority: 15)]
    public function setupCache(): PageCacheDB
    {
        $this->cache ??= new PageCacheDB(conn: $this->db, logger: new ConsoleLogger());
        $this->cache->reinitialize();
        return $this->cache;
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
