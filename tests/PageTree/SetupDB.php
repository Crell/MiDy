<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\SetupFilesystem;
use PDO;
use PHPUnit\Framework\Attributes\Before;
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;
use Yiisoft\Db\Sqlite\Dsn;

trait SetupDB
{
    use SetupFilesystem;

    private Connection $conn;

    #[Before(20)]
    public function setupMockDb(): void
    {
//        return $this->db ??= new \PDO('sqlite::memory:');
    }

    #[Before(20)]
    public function setupconnectionAndDB(): void
    {
        // Dsn.
        $dsn = new Dsn('sqlite', 'memory')->asString();

        // PSR-16 cache implementation.
        $fileCache = new FileCache($this->cachePath . '/yii');

        // Schema cache.
        $schemaCache = new SchemaCache($fileCache);
        // @todo I don't know why the cache is failing now for DML queries,
        //   but I am not going to spend time figuring it out.
        $schemaCache->setEnabled(false);

        // PDO Driver.
        $pdoDriver = new Driver($dsn, attributes: [
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);

        // Connection.
        $this->conn = new Connection($pdoDriver, $schemaCache);
    }
}
