<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\PageTree\Model\File;
use Crell\MiDy\PageTree\Model\PageRecord;
use Crell\MiDy\PageTree\Model\PageData;
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
            summary      TEXT               not null,
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

    /**
     * @return array<ParsedFolder>
     */
    public function childFolders(string $parentLogicalPath): array
    {
        $result = $this->conn->createCommand('SELECT * FROM folder WHERE parent=:logicalPath AND NOT parent=logicalPath', [
            ':logicalPath' => $parentLogicalPath,
        ])->queryAll();
        return array_map($this->instantiateFolder(...), $result);
    }

    public function writePage(PageData $page): void
    {
        $this->conn->createCommand()
            ->delete('page', 'logicalPath = :logicalPath')
            ->bindParam('logicalPath', $page->logicalPath)
            ->execute();

        $this->conn->createCommand()->insert('page', [
            'logicalPath' => $page->logicalPath,
            'folder' => $page->folder,
            'files' => json_encode($page->files, JSON_THROW_ON_ERROR),
            'title' => $page->title,
            'order' => $page->order,
            'hidden' => $page->hidden,
            'routable' => $page->routable,
            'isFolder' => $page->isFolder,
            'publishDate' => $page->publishDate->format('c'),
            'lastModifiedDate' => $page->lastModifiedDate->format('c'),
            'pathName' => $page->pathName,
            'summary' => $page->summary,
            'tags' => json_encode($page->tags, JSON_THROW_ON_ERROR),
        ])->execute();
    }

    public function readPage(string $path): ?PageRecord
    {
        $result = $this->conn
            ->createCommand("SELECT logicalPath, folder, files, title, summary, \"order\", hidden, routable, isFolder, publishDate, lastModifiedDate, tags FROM page WHERE logicalPath=:logicalPath")
            ->bindParam(':logicalPath', $path)
            ->queryOne();

        if (!$result) {
            return null;
        }

        return $this->instantiatePage($result);
    }

    /**
     * @param string|null $folder
     * @param bool $deep
     * @param int $limit
     * @param int $offset
     * @param array $orderBy
     *   An associative array of properties to sort by. The key is the field name,
     *   the value is either SORT_ASC or SORT_DESC, as desired. Regardless of what
     *   is provided, the sort list will be appended with: order, title, path, to
     *   ensure queries are always deterministic.
     *
     * @todo publishedAfter,
     *      titleContains
     */
    public function queryPages(
        ?string $folder = null,
        bool $deep = false,
        bool $includeHidden = false,
        bool $routableOnly = true,
        array $anyTag = [],
        ?\DateTimeInterface $publishedBefore = new \DateTimeImmutable(),
        array $orderBy = [],
        int $limit = self::DefaultPageSize,
        int $offset = 0,
    ): QueryResult {
        $query = new Query($this->conn)
            ->select(['logicalPath', 'folder', 'files', 'title', 'summary', 'order', 'hidden', 'routable', 'isFolder', 'publishDate', 'lastModifiedDate', 'tags'])
            ->from('page')
            ->andWhere('NOT logicalPath = folder')  // To exclude index pages as children.
        ;

        // @todo Validate the $orderBy format with a nice error message.

        // Order by these after whatever the user provides.
        $orderBy += [
            'order' => SORT_ASC,
            'title' => SORT_ASC,
            'logicalPath' => SORT_ASC,
        ];

        foreach ($orderBy as $field => $sortOrder) {
            $query->addOrderBy(["[[$field]]" => $sortOrder]);
        }

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

        if ($publishedBefore) {
            $query->andWhere(['<=', 'publishDate', $publishedBefore->format('c')]);
        }

        if ($folder) {
            if (!$deep) {
                $query->andWhere(['folder' => $folder]);
            } else {
                $cond = new OrCondition([
                    ['folder' => $folder],
                    'folder LIKE :folder',
                ]);
                $query->andWhere($cond)->addParams([':folder' => $folder . '/%']);
            }
        }

        $total = $query->count();

        $query
            ->limit($limit)
            ->offset($offset);
        $pages = array_map($this->instantiatePage(...), $query->all());

        return new QueryResult(
            total: $total,
            pages: $pages
        );
    }

    /**
     * Returns a list of all paths that exist in the system.
     *
     * This is for the pre-generator logic.  Don't use it otherwise.
     */
    public function allPaths(): iterable
    {
        return $this->conn->createCommand("SELECT logicalPath from page")->queryColumn();
    }

    /**
     * Returns a list of all files that exist in the system.
     *
     * This is for the pre-generator logic.  Don't use it otherwise.
     *
     * @return iterable<File>
     */
    public function allFiles(): iterable
    {
        $filesLines = $this->conn->createCommand("SELECT files from page")->queryColumn();
        foreach ($filesLines as $result) {
            $records = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
            yield from array_map($this->instantiateFile(...), $records);
        }
    }

    private function instantiatePage(array $record): PageRecord
    {
        $files = json_decode($record['files'], true, 512, JSON_THROW_ON_ERROR);
        $record['files'] = array_map($this->instantiateFile(...), $files);

        // SQLite gives back badly typed data, so we have to clean it up a bit.
        $record['hidden'] = (bool)$record['hidden'];
        $record['routable'] = (bool)$record['routable'];
        $record['isFolder'] = (bool)$record['isFolder'];
        $record['publishDate'] = new \DateTimeImmutable($record['publishDate']);
        $record['lastModifiedDate'] = new \DateTimeImmutable($record['lastModifiedDate']);

        $record['tags'] = json_decode($record['tags'], true, 512, JSON_THROW_ON_ERROR);

        return new PageRecord(...$record);
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

    private function instantiateFile(array $record): File
    {
        return new File(...$record);
    }

    public function inTransaction(\Closure $closure): mixed
    {
        return $this->conn->transaction($closure);
    }
}
