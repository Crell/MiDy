<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\PageTree\BasicPageInformation;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;
use Psr\Log\LoggerInterface;

class PageCacheDB
{
    private const string FolderTableDdl = <<<END
        create table folder (
            logicalPath  text               not null
                primary key,
            physicalPath text               not null,
            parent       text                  not null,
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
            pathName     TEXT               not null,
            constraint page_pk
                primary key (logicalPath, ext),
            foreign key (folder) references folder(logicalPath)
                on delete cascade
        );
    END;

    private const string FileTagTableDdl = <<<END
        create table file_tag (
            logicalPath varchar not null,
            ext varchar not null,
            tag varchar not null,
            constraint page_pk
                primary key (logicalPath, ext, tag),
            foreign key (logicalPath, ext) references file(logicalPath, ext)
                on delete cascade
        );
    END;


    private const string WriteFolderSql = <<<END
        INSERT INTO
        folder (logicalPath, physicalPath, parent, flatten, mtime, title)
        VALUES(:logicalPath, :physicalPath, :parent, :flatten, :mtime, :title)
        ON CONFLICT(logicalPath) DO UPDATE SET
            logicalPath = excluded.logicalPath,
            physicalPath = excluded.physicalPath,
            parent = excluded.parent,
            flatten = excluded.flatten,
            mtime = excluded.mtime,
            title = excluded.title
    END;

    private const string DeleteFolderSql = 'DELETE FROM folder WHERE logicalPath=?';
    private const string ReadFolderSql = 'SELECT * FROM folder WHERE logicalPath=?';
    private const string ChildFoldersSql = 'SELECT * FROM folder WHERE parent=? AND NOT parent=logicalPath';

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
            summary,
            pathName
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
            :summary,
            :pathName
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
            summary = excluded.summary,
            pathName = excluded.pathName
    END;

    private const string DeleteFileSql = 'DELETE FROM file WHERE logicalPath=? AND ext=?';
    private const string ReadFileSql = 'SELECT * FROM file WHERE logicalPath=? AND ext=?';
    private const string ReadFilesSql = 'SELECT * FROM file WHERE folder=? ORDER BY "order", title';
    private const string DeleteTagSql = 'DELETE FROM file_tag WHERE logicalPath=? AND ext=?';
    private const string WriteTagSql = <<<END
        INSERT INTO file_tag (logicalPath, ext, tag) VALUES (:logicalPath, :ext, :tag)
        ON CONFLICT (logicalPath, ext, tag) DO NOTHING
    END;

    private \PDOStatement $writeFolderStmt { get => $this->writeFolderStmt ??= $this->conn->prepare(self::WriteFolderSql); }
    private \PDOStatement $readFolderStmt { get => $this->readFolderStmt ??= $this->conn->prepare(self::ReadFolderSql); }
    private \PDOStatement $deleteFolderStmt { get => $this->deleteFolderStmt ??= $this->conn->prepare(self::DeleteFolderSql); }
    private \PDOStatement $childFolderStmt { get => $this->childFolderStmt ??= $this->conn->prepare(self::ChildFoldersSql); }

    private \PDOStatement $writeFileStmt { get => $this->writeFileStmt ??= $this->conn->prepare(self::WriteFileSql); }
    private \PDOStatement $readFileStmt { get => $this->readFileStmt ??= $this->conn->prepare(self::ReadFileSql); }
    private \PDOStatement $readFilesStmt { get => $this->readFilesStmt ??= $this->conn->prepare(self::ReadFilesSql); }
    private \PDOStatement $deleteFileStmt { get => $this->deleteFileStmt ??= $this->conn->prepare(self::DeleteFileSql); }

    private \PDOStatement $deleteTagsStmt { get => $this->deleteTagsStmt ??= $this->conn->prepare(self::DeleteTagSql); }
    private \PDOStatement $writeTagsStmt { get => $this->writeTagsStmt ??= $this->conn->prepare(self::WriteTagSql); }

    public function __construct(
        private \PDO $conn,
        private Serde $serde = new SerdeCommon(),
        private ?LoggerInterface $logger = null,
    ) {
        $this->ensureTables();
    }

    private function ensureTables(): void
    {
        // If there's no file table, assume there's nothing and recreate all tables.
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='file'";
        $names = $this->conn->query($sql)->fetchColumn();
        if (!$names) {
            $this->reinitialize();
        }
    }

    /**
     * Recreate all tables.
     */
    public function reinitialize(): void
    {
        $this->inTransaction(function (\PDO $conn) {
            $conn->exec('DROP TABLE IF EXISTS file');
            $conn->exec('DROP TABLE IF EXISTS folder');
            $conn->exec('DROP TABLE IF EXISTS file_tag');

            $conn->exec(self::FolderTableDdl);
            $conn->exec(self::FileTableDdl);
            $conn->exec(self::FileTagTableDdl);
        });
    }

    public function writeFolder(ParsedFolder $folder): void
    {
        $this->writeFolderStmt->execute([
            'logicalPath' => $folder->logicalPath,
            'physicalPath' => $folder->physicalPath,
            'parent' => $folder->parent,
            'flatten' => $folder->flatten,
            'mtime' => $folder->mtime,
            'title' => $folder->title,
        ]);
    }

    public function readFolder(string $logicalPath): ?ParsedFolder
    {
        $this->readFolderStmt->execute([$logicalPath]);
        $record = $this->readFolderStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$record) {
            return null;
        }

        return $this->instantiateFolder($record);
    }

    /**
     * This will also delete records for any files in this folder.
     */
    public function deleteFolder(string $logicalPath): void
    {
        $this->deleteFolderStmt->execute([$logicalPath]);
    }

    /**
     * @return array<ParsedFolder>
     */
    public function childFolders(string $parentLogicalPath): array
    {
        $this->childFolderStmt->execute([$parentLogicalPath]);
        return array_map($this->instantiateFolder(...), $this->childFolderStmt->fetchAll(\PDO::FETCH_ASSOC));
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
            ':pathName' => $file->pathName,
        ]);

        $this->deleteTagsStmt->execute([$file->logicalPath, $file->ext]);
        foreach ($file->frontmatter->tags as $tag) {
            $this->writeTagsStmt->execute([
                ':logicalPath' => $file->logicalPath,
                ':ext' => $file->ext,
                ':tag' => $tag,
            ]);
        }
    }

    public function deleteFile(string $logicalPath, string $ext): void
    {
        $this->deleteFileStmt->execute([$logicalPath, $ext]);
    }

    public function readFile(string $logicalPath, string $ext): ?ParsedFile
    {
        $this->readFileStmt->execute([$logicalPath, $ext]);
        $record = $this->readFileStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$record) {
            return null;
        }

        return $this->instantiateFile($record);
    }

    /**
     * @return array<ParsedFile>
     */
    public function readFiles(string $folderPath): array
    {
        $this->readFilesStmt->execute([$folderPath]);
        return array_map($this->instantiateFile(...), $this->readFilesStmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function instantiateFile(array $record): ParsedFile
    {
        // SQLite gives back badly typed data, so we have to clean it up a bit.
        $record['hidden'] = (bool)$record['hidden'];
        $record['routable'] = (bool)$record['routable'];
        $record['publishDate'] = new \DateTimeImmutable($record['publishDate']);
        $record['lastModifiedDate'] = new \DateTimeImmutable($record['lastModifiedDate']);
        $record['frontmatter'] = $this->serde->deserialize($record['frontmatter'], from: 'json', to: BasicPageInformation::class);

        return new ParsedFile(...$record);
    }

    private function instantiateFolder(array $record): ParsedFolder
    {
        // Parent isn't part of the constructor.  It's just a derived field,
        // so skip it.
        unset($record['parent']);

        // SQLite gives back badly typed data, so we have to clean it up a bit.
        $record['flatten'] = (bool)$record['flatten'];
        return new ParsedFolder(...$record);
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
            $this->logger?->error($e->getMessage(), ['exception' => $e]);
            return null;
        }
    }
}
