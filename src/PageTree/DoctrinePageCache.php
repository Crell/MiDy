<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

class DoctrinePageCache implements PageCache
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

    public function __construct(
        private readonly Connection $conn,
    ) {
        $this->ensureTables();
    }

    private function ensureTables(): void
    {
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='page'";
        $name = $this->conn->executeQuery($sql)->fetchOne();

        if (!$name) {
            $this->reinitialize();
        }
    }

    /**
     * Recreate all tables.
     */
    public function reinitialize(): void
    {
        $this->conn->transactional(function (Connection $conn) {
            $conn->executeQuery('DROP TABLE IF EXISTS page');
            $conn->executeQuery('DROP TABLE IF EXISTS folder');
            $conn->executeQuery(self::FolderTableDdl);
            $conn->executeQuery(self::PageTableDdl);
        });
    }

    public function writeFolder(ParsedFolder $folder): void
    {
        // Seriously, why does anyone use Doctrine DBAL when it doesn't have a native upsert?
        // Every database has syntax for it now!

        $qb = $this->conn->createQueryBuilder();

        $qb->delete('folder')
            ->where('logicalPath=' . $qb->createNamedParameter($folder->logicalPath))
            ->executeQuery();

        $qb = $this->conn->createQueryBuilder();
        $qb->insert('folder')
            ->values([
                'logicalPath' => $qb->createNamedParameter($folder->logicalPath),
                'physicalPath' => $qb->createNamedParameter((string)$folder->physicalPath),
                'parent' => $qb->createNamedParameter($folder->parent),
                'flatten' => $qb->createNamedParameter($folder->flatten),
                'mtime' => $qb->createNamedParameter($folder->mtime),
                'title' => $qb->createNamedParameter($folder->title),
            ])
            ->executeQuery();
    }

    public function readFolder(LogicalPath $logicalPath): ?ParsedFolder
    {
        $record = $this->conn
            ->executeQuery('SELECT * FROM folder WHERE logicalPath=:logicalPath', ['logicalPath' => $logicalPath])
            ->fetchAssociative();

        if (!$record) {
            return null;
        }

        return $this->instantiateFolder($record);
    }

    /**
     * This will also delete records for any files in this folder.
     */
    public function deleteFolder(LogicalPath $logicalPath): void
    {
        $this->conn
            ->executeQuery("DELETE FROM folder WHERE logicalPath = :logicalPath", ['logicalPath' => $logicalPath]);
    }

    /**
     * @return array<ParsedFolder>
     */
    public function childFolders(LogicalPath $parentLogicalPath): array
    {
        $result = $this->conn->executeQuery('SELECT * FROM folder WHERE parent=:logicalPath AND NOT parent=logicalPath', [
            'logicalPath' => $parentLogicalPath,
        ])->fetchAllAssociative();
        return array_map($this->instantiateFolder(...), $result);
    }

    public function writePage(PageData $page): void
    {
        $this->conn->executeQuery("DELETE FROM page WHERE logicalPath = :logicalPath", ['logicalPath' => $page->logicalPath]);

        // Holy hell, Doctrine, how do you not have an "insert this array" method?
        // Why am I using Doctrine again?

        $qb = $this->conn->createQueryBuilder();
        $qb->insert('page')->values([
            'logicalPath' => $qb->createNamedParameter($page->logicalPath),
            'folder' => $qb->createNamedParameter($page->folder),
            'files' => $qb->createNamedParameter(json_encode($page->files, JSON_THROW_ON_ERROR)),
            'title' => $qb->createNamedParameter($page->title),
            '`order`' => $qb->createNamedParameter($page->order),
            // Doctrine isn't smart enough to figure out that it needs to cast bools to
            // ints on SQLite and MySQL, so we have to do it for it. Because Doctrine is dumb.
            'hidden' => $qb->createNamedParameter((int)$page->hidden),
            'routable' => $qb->createNamedParameter((int)$page->routable),
            'isFolder' => $qb->createNamedParameter((int)$page->isFolder),
            'publishDate' => $qb->createNamedParameter($page->publishDate->format('c')),
            'lastModifiedDate' => $qb->createNamedParameter($page->lastModifiedDate->format('c')),
            'pathName' => $qb->createNamedParameter($page->pathName),
            'summary' => $qb->createNamedParameter($page->summary),
            'tags' => $qb->createNamedParameter(json_encode($page->tags, JSON_THROW_ON_ERROR)),
        ])->executeQuery();
    }

    public function readPage(LogicalPath $path): ?PageRecord
    {
        $result = $this->conn
            ->executeQuery("SELECT logicalPath, folder, files, title, summary, \"order\", hidden, routable, isFolder, publishDate, lastModifiedDate, tags FROM page WHERE logicalPath=:logicalPath", ['logicalPath' => $path])
            ->fetchAssociative();

        if (!$result) {
            return null;
        }

        return $this->instantiatePage($result);
    }

    public function queryPages(
        string|LogicalPath|null $folder = null,
        bool $deep = false,
        bool $includeHidden = false,
        bool $routableOnly = true,
        array $anyTag = [],
        ?\DateTimeInterface $publishedBefore = new \DateTimeImmutable(),
        array $orderBy = [],
        int $limit = self::DefaultPageSize,
        int $offset = 0,
        array $exclude = [],
    ): QueryResult {
        $query = $this->conn->createQueryBuilder();

        $query
            ->select('logicalPath', 'folder', 'files', 'title', 'summary', '`order`', 'hidden', 'routable', 'isFolder', 'publishDate', 'lastModifiedDate', 'tags')
            ->from('page')
            ->where('NOT logicalPath = folder')  // To exclude index pages as children.
        ;

        // @todo Validate the $orderBy format with a nice error message.

        // Order by these after whatever the user provides.
        $orderBy += [
            '`order`' => SORT_ASC,
            'title' => SORT_ASC,
            'logicalPath' => SORT_ASC,
        ];

        foreach ($orderBy as $field => $sortOrder) {
            $order = $sortOrder === SORT_ASC ? 'asc' : 'desc';
            $query->addOrderBy($field, $order);
        }

        if (!$includeHidden) {
            $query->andWhere('hidden = 0');
        }
        if ($routableOnly) {
            $query->andWhere('routable = 1');
        }

        // Trim out any empty strings or nulls, as those are invalid tags.
        if ($anyTag = array_filter($anyTag)) {
            $query->from('json_each(tags)');
            $query->andWhere('json_each.value in (:tags)')
                ->setParameter('tags', $anyTag, ArrayParameterType::STRING);
            // The json_each() call results in a row-per-tag, so we need to
            // filter that out in case we match on more than one tag.
            $query->distinct();
        }

        if ($publishedBefore) {
            $query->andWhere('publishDate <= ' . $query->createNamedParameter($publishedBefore->format('c')));
        }

        if ($folder) {
            if (!$deep) {
                $query->andWhere('folder = ' . $query->createNamedParameter($folder));
            } else {
                $query->andWhere($query->expr()->or(
                    $query->expr()->eq('folder', "'" . $folder . "'"), // Doctrine, why do you need me to quote this myself?
                    $query->expr()->like('folder', ':folder'),
                ))
                ->setParameter('folder', $folder . '/%');
            }
        }

        if ($exclude) {
            // Never trust arrays.
            $query->andWhere('logicalPath NOT IN (:paths)')
                ->setParameter('paths', array_values($exclude), ArrayParameterType::STRING);
        }

        $totalQuery = clone($query);
        $totalQuery
            ->select('COUNT(DISTINCT(logicalPath))')
            ->resetOrderBy()
        ;
        $total = $totalQuery->executeQuery()->fetchOne();

        $query
            ->setMaxResults($limit)
            ->setFirstResult($offset)
        ;
        $pages = array_map($this->instantiatePage(...), $query->fetchAllAssociative());

        return new QueryResult(
            total: $total,
            pages: $pages
        );
    }

    public function allPaths(): iterable
    {
        return $this->conn->executeQuery("SELECT logicalPath from page")->fetchFirstColumn();
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
        $filesLines = $this->conn->executeQuery("SELECT files from page")->fetchFirstColumn();
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
        return $this->conn->transactional($closure);
    }
}
