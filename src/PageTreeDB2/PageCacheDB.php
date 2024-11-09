<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

class PageCacheDB
{
    private const string FolderTableDdl = <<<END
        create table folder (
            logicalPath  varchar               not null
                primary key,
            physicalPath varchar               not null,
            flatten      int     default 0     not null,
            mtime        integer               not null,
            title        string                not null
        );
    END;

    private const string FileTableDdl = <<<END
        create table file (
            logicalPath  varchar            not null,
            ext          varchar            not null,
            physicalPath varchar            not null,
            mtime        integer            not null,
            title        varchar            not null,
            folder       varchar            not null on conflict abort
                constraint page_folder_logicalPath_fk
                    references folder,
            "order"      integer default 0  not null,
            hidden       integer default 0  not null,
            routable     integer default 0  not null,
            publishDate  string             not null,
            lastModifiedDate  string        not null,
            summary      TEXT    default '' not null,
            frontmatter  TEXT    default '' not null, --JSON
            constraint page_pk
                primary key (logicalPath, ext),
            foreign key (folder) references folder(logicalPath)
                on delete cascade 
        );
    END;

    private const string WriteFolderSql = <<<END
        INSERT INTO
        folder (logicalPath, physicalPath, flatten, mtime, title)
        VALUES(:logicalPath, :physicalPath, :flatten, :mtime, :title)
        ON CONFLICT(logicalPath) DO UPDATE SET
            logicalPath = excluded.logicalPath,
            physicalPath = excluded.physicalPath,
            flatten = excluded.flatten,
            mtime = excluded.mtime,
            title = excluded.title
    END;

    private const string DeleteFolderSql = 'DELETE FROM folder WHERE logicalPath=?';
    private const string ReadFolderSql = 'SELECT * FROM folder WHERE logicalPath=?';

    private const string WriteFileSql = <<<END
        INSERT INTO
        file (
            logicalPath,
            ext,
            physicalPath,
            mtime,
            title,
            folder,
            "order",
            hidden,
            routable,
            publishDate,
            lastModifiedDate,
            frontmatter,
            summary
        )
        VALUES(
            :logicalPath,
            :ext,
            :physicalPath,
            :mtime,
            :title,
            :folder,
            :order,
            :hidden,
            :routable,
            :publishDate,
            :lastModifiedDate,
            :frontmatter,
            :summary
        )
        ON CONFLICT(logicalPath, ext) DO UPDATE SET
            logicalPath = excluded.logicalPath,
            ext = excluded.ext,
            physicalPath = excluded.physicalPath,
            mtime = excluded.mtime,
            title = excluded.title,
            folder = excluded.folder,
            "order" = excluded."order",
            hidden = excluded.hidden,
            routable = excluded.routable,
            publishDate = excluded.publishDate,
            lastModifiedDate = excluded.lastModifiedDate,
            frontmatter = excluded.frontmatter,
            summary = excluded.summary
    END;

    private const string DeleteFileSql = 'DELETE FROM file WHERE logicalPath=? AND ext=?';
    private const string ReadFileSql = 'SELECT * FROM file WHERE logicalPath=?';

    private \PDOStatement $writeFolderStmt { get => $this->writeFolderStmt ??= $this->conn->prepare(self::WriteFolderSql); }
    private \PDOStatement $readFolderStmt { get => $this->readFolderStmt ??= $this->conn->prepare(self::ReadFolderSql); }
    private \PDOStatement $deleteFolderStmt { get => $this->deleteFolderStmt ??= $this->conn->prepare(self::DeleteFolderSql); }

    private \PDOStatement $writeFileStmt { get => $this->writeFileStmt ??= $this->conn->prepare(self::WriteFileSql); }
    private \PDOStatement $readFileStmt { get => $this->readFileStmt ??= $this->conn->prepare(self::ReadFileSql); }
    private \PDOStatement $deleteFileStmt { get => $this->deleteFileStmt ??= $this->conn->prepare(self::DeleteFileSql); }


    public function __construct(
        private \PDO $conn,
    ) {}

    /**
     * Recreate all tables.
     */
    public function reinitialize(): void
    {
        $this->inTransaction(function (\PDO $conn) {
            $conn->exec('DROP TABLE IF EXISTS file');
            $conn->exec('DROP TABLE IF EXISTS folder');

            $conn->exec(self::FolderTableDdl);
            $conn->exec(self::FileTableDdl);
        });
    }

    public function writeFolder(ParsedFolder $folder): void
    {
        $this->writeFolderStmt->execute([
            'logicalPath' => $folder->logicalPath,
            'physicalPath' => $folder->physicalPath,
            'flatten' => $folder->flatten,
            'mtime' => $folder->mtime,
            'title' => $folder->title,
        ]);
    }

    public function readFolder(string $logicalPath)
    {
        $this->readFolderStmt->execute([$logicalPath]);
        $record = $this->readFolderStmt->fetch(\PDO::FETCH_ASSOC);
        // SQLite gives back badly typed data, so we have to clean it up a bit.
        $record['flatten'] = (bool)$record['flatten'];
        return new ParsedFolder(...$record);
    }

    /**
     * This will also delete records for any files in this folder.
     */
    public function deleteFolder(string $logicalPath): void
    {
        $this->deleteFolderStmt->execute([$logicalPath]);
    }

    public function writeFile(ParsedFile $file): void
    {
        $this->writeFileStmt->execute([
            ':logicalPath' => $file->logicalPath,
            ':ext' => $file->ext,
            ':physicalPath' => $file->physicalPath,
            ':mtime' => $file->mtime,
            ':title' => $file->title,
            ':folder' => $file->folder,
            ':order' => $file->order,
            ':hidden' => $file->hidden,
            ':routable' => $file->routable,
            ':publishDate' => $file->publishDate->format('c'),
            ':lastModifiedDate' => $file->lastModifiedDate->format('c'),
            ':frontmatter' => json_encode($file->frontmatter, JSON_THROW_ON_ERROR),
            ':summary' => $file->summary,
        ]);
    }

    public function deleteFile(string $logicalPath, string $ext)
    {

    }

    public function readFile(string $logicalPath, string $ext)
    {

    }

    public function inTransaction(\Closure $closure): mixed
    {
        try {
            $this->conn->beginTransaction();
            $ret = $closure($this->conn);
            $this->conn->commit();
            return $ret;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }
}
