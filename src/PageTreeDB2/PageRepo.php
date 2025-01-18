<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\PageTree\BasicPageInformation;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\Condition\OrCondition;
use Yiisoft\Db\Sqlite\Connection;

class PageRepo
{
    public const int DefaultPageSize = 10;

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

    private const string PageTableDdl = <<<END
        create table page (
            logicalPath  varchar            not null,
            folder       varchar            not null on conflict abort
                constraint page_folder_logicalPath_fk
                    references folder,
            files        JSONB default '' not null,
            title        TEXT default '' not null,
            "order"      integer default 0  not null,
            hidden       integer default 0  not null,
            routable     integer default 0  not null,
            isFolder     integer default 0  not null,
            publishDate  string             not null,
            lastModifiedDate  string        not null,
            pathName     TEXT               not null,
            tags         JSONB default '[]' not null,
            constraint page_pk
                primary key (logicalPath),
            foreign key (folder) references folder(logicalPath)
                on delete cascade
        );
    END;

    private const string ReadFolderSql = 'SELECT * FROM folder WHERE logicalPath=:logicalPath';

    public function __construct(
        private readonly Connection $conn,
        private readonly Serde $serde = new SerdeCommon(),
    ) {
        $this->ensureTables();
    }

    private function ensureTables(): void
    {
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='page'";
        $name = $this->conn->createCommand($sql)->queryScalar();

        if (!$name) {
            $this->reinitialize();
        }
    }

    /**
     * Recreate all tables.
     */
    public function reinitialize(): void
    {
        $this->conn->transaction(function (Connection $conn) {
            $conn->createCommand('DROP TABLE IF EXISTS page')->execute();
            $conn->createCommand('DROP TABLE IF EXISTS folder')->execute();
            $conn->createCommand(self::FolderTableDdl)->execute();
            $conn->createCommand(self::PageTableDdl)->execute();
        });
    }

    public function writeFolder(ParsedFolder $folder): void
    {
        $this->conn->createCommand()->upsert('folder', [
            'logicalPath' => $folder->logicalPath,
            'physicalPath' => $folder->physicalPath,
            'parent' => $folder->parent,
            'flatten' => $folder->flatten,
            'mtime' => $folder->mtime,
            'title' => $folder->title,
        ], [
            'physicalPath' => $folder->physicalPath,
            'parent' => $folder->parent,
            'flatten' => $folder->flatten,
            'mtime' => $folder->mtime,
            'title' => $folder->title,
        ])->execute();
    }

    public function readFolder(string $logicalPath): ?ParsedFolder
    {
        $record = $this->conn->createCommand(self::ReadFolderSql)
            ->bindParam('logicalPath', $logicalPath)
            ->queryOne();

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
        $this->conn->createCommand()
            ->delete('folder', 'logicalPath = :logicalPath')
            ->bindParam('logicalPath', $logicalPath)
            ->execute();
    }

    public function writePage(PageRecord $page): void
    {
        $this->conn->createCommand()
            ->delete('page', 'logicalPath = :logicalPath')
            ->bindParam('logicalPath', $page->logicalPath)
            ->execute();

        $this->conn->createCommand()->insert('page', [
            'logicalPath' => $page->logicalPath,
            'folder' => $page->folder,
            'files' => json_encode($page->files, JSON_THROW_ON_ERROR),
            'order' => $page->order,
            'hidden' => $page->hidden,
            'routable' => $page->routable,
            'isFolder' => $page->isFolder,
            'publishDate' => $page->publishDate->format('c'),
            'lastModifiedDate' => $page->lastModifiedDate->format('c'),
            'pathName' => $page->pathName,
            'tags' => json_encode($page->tags, JSON_THROW_ON_ERROR),
        ])->execute();
    }

    public function readPage(string $path): ?PageRecord
    {
        $result = $this->conn
            ->createCommand("SELECT files FROM page WHERE logicalPath=:logicalPath")
            ->bindParam(':logicalPath', $path)
            ->queryScalar();

        $files = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

        $loadedFiles = array_map($this->instantiateFile(...), $files);

        if (empty($loadedFiles)) {
            return null;
        }

        return new PageRecord(
            logicalPath: $path,
            folder: $loadedFiles[0]->folder,
            files: $loadedFiles,
        );
    }

    /**
     * @param string|null $folder
     * @param bool $deep
     * @param int $limit
     * @param int $offset
     *
     * @todo publishedBefore, publishedAfter,
     *      titleContains
     *
     * @todo Ordering
     */
    public function queryPages(
        ?string $folder = null,
        bool $deep = false,
        bool $includeHidden = false,
        bool $routableOnly = true,
        array $anyTag = [],
        int $limit = self::DefaultPageSize,
        int $offset = 0,
    ): QueryResult {
        $query = new Query($this->conn)
            ->select(['logicalPath', 'folder', 'files'])
            ->from('page')
            ->limit($limit)
            ->offset($offset)
        ;

        if (!$includeHidden) {
            $query->andWhere(['hidden' => 0]);
        }
        if ($routableOnly) {
            $query->andWhere(['routable' => 1]);
        }

        if ($anyTag) {
            $query->from([...$query->getFrom(), 'json_each(tags)']);
            $query->andWhere(['in', "json_each.value", $anyTag]);
            // The json_each() call results in a row-per-tag, so we need to
            // filter that out in case we match on more than one tag.
            $query->distinct();
        }

        if ($folder) {
            if (!$deep) {
                $query->andWhere(['folder' => $folder]);
            } else {
                // @todo This isn't quite right, as Yii by default
                //   sticks a % both before and after the value. But it's not clear how
                //   to make it do just a prefix search (% only at the end)
                $query->andWhere(['like', 'folder', $folder]);
            }
        }

        $total = $query->count();
        $pages = array_map($this->instantiatePage(...), $query->all());

        return new QueryResult(
            total: $total,
            pages: $pages
        );
    }

    private function instantiatePage(array $record): PageRecord
    {
        $files = json_decode($record['files'], true, 512, JSON_THROW_ON_ERROR);

        $loadedFiles = array_map($this->instantiateFile(...), $files);

        return new PageRecord(
            logicalPath: $record['logicalPath'],
            folder: $record['folder'],
            files: $loadedFiles,
        );
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

    private function instantiateFile(array $record): ParsedFile
    {
        // Upcast some fields back to PHP objects.
        $record['publishDate'] = new \DateTimeImmutable($record['publishDate']['date'], new \DateTimeZone($record['publishDate']['timezone']));
        $record['lastModifiedDate'] = new \DateTimeImmutable($record['lastModifiedDate']['date'], new \DateTimeZone($record['lastModifiedDate']['timezone']));
        $record['frontmatter'] = $this->serde->deserialize($record['frontmatter'], from: 'array', to: BasicPageInformation::class);

        return new ParsedFile(...$record);
    }
}

