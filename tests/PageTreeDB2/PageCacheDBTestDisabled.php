<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\PageTree\BasicPageInformation;
use Crell\MiDy\SetupFilesystem;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PageCacheDBTestDisabled extends TestCase
{
    use SetupFilesystem;
    use SetupDB;
    use MakerUtils;

    private ParsedFile $parsedFile;
    private ParsedFolder $parsedFolder;

    #[Before]
    public function setupData(): void
    {
        $this->parsedFolder = new ParsedFolder(
            '/foo',
            '/foo',
            0,
            false,
            'Foo',
        );

        $this->parsedFile = new ParsedFile(
            logicalPath: '/foo/bar',
            ext: 'md',
            physicalPath: '/foo/bar.md',
            mtime: 123456,
            title: 'Foo',
            folder: '/foo',
            order: 2,
            hidden: false,
            routable: true,
            publishDate: new \DateTimeImmutable('2024-10-31'),
            lastModifiedDate: new \DateTimeImmutable('2024-10-31'),
            frontmatter: new BasicPageInformation(),
            summary: '',
            pathName: 'bar',
        );
    }

    #[Test, DoesNotPerformAssertions]
    public function reinitialize_creates_tables_if_they_dont_exist(): void
    {
        $cache = new PageCacheDB($this->yiiConn);

        $cache->reinitialize();

        // These will throw an exception if the tables do not exist.
        $this->db->query("SELECT 1 FROM folder")->execute();
        $this->db->query("SELECT 1 FROM file")->execute();
    }

    #[Test, DoesNotPerformAssertions]
    public function reinitialize_recreates_tables_if_they_do_exist(): void
    {
        $cache = new PageCacheDB($this->yiiConn);

        $this->db->exec('CREATE TABLE IF NOT EXISTS folder(fake int)');

        $cache->reinitialize();

        // These will throw an exception if the tables do not exist.
        $this->db->query("SELECT 1 FROM folder")->execute();
        $this->db->query("SELECT 1 FROM file")->execute();
    }

    #[Test]
    public function can_write_new_folder(): void
    {
        $cache = new PageCacheDB($this->yiiConn);

        $cache->reinitialize();

        $folder = $this->parsedFolder;

        $cache->writeFolder($folder);

        $stmt = $this->db->query("SELECT * FROM folder WHERE logicalPath='/foo'");
        $record = $stmt->fetchObject();
        self::assertEquals($folder->physicalPath, $record->physicalPath);
    }

    #[Test]
    public function can_write_updated_folder(): void
    {
        $cache = new PageCacheDB($this->yiiConn);

        $cache->reinitialize();

        $folder = $this->parsedFolder;
        $cache->writeFolder($folder);

        $newFolder = new ParsedFolder('/foo', '/foo', 123456, true, 'Foo2');
        $cache->writeFolder($newFolder);

        $stmt = $this->db->query("SELECT * FROM folder WHERE logicalPath='/foo'");
        $record = $stmt->fetchObject();
        self::assertEquals($newFolder->physicalPath, $record->physicalPath);
        self::assertEquals($newFolder->mtime, $record->mtime);
        self::assertEquals($newFolder->flatten, $record->flatten);
        self::assertEquals($newFolder->title, $record->title);
    }

    #[Test]
    public function can_read_folder(): void
    {
        $cache = new PageCacheDB($this->yiiConn);

        $cache->reinitialize();

        $folder = $this->parsedFolder;
        $cache->writeFolder($folder);

        $savedFolder = $cache->readFolder('/foo');

        self::assertEquals($folder->physicalPath, $savedFolder->physicalPath);
        self::assertEquals($folder->mtime, $savedFolder->mtime);
        self::assertEquals($folder->flatten, $savedFolder->flatten);
        self::assertEquals($folder->title, $savedFolder->title);
    }

    #[Test]
    public function returns_null_for_missing_folder(): void
    {
        $cache = new PageCacheDB($this->yiiConn);

        $cache->reinitialize();

        $savedFolder = $cache->readFolder('/foo');

        self::assertNull($savedFolder);
    }

    #[Test]
    public function can_delete_folder(): void
    {
        $cache = new PageCacheDB($this->yiiConn);

        $cache->reinitialize();

        $folder = $this->parsedFolder;
        $cache->writeFolder($folder);

        $cache->deleteFolder('/foo');

        $stmt = $this->db->query("SELECT * FROM folder WHERE logicalPath='/foo'");
        $record = $stmt->fetchObject();

        self::assertFalse($record);
    }

    #[Test]
    public function can_write_new_file(): void
    {
        $cache = new PageCacheDB($this->yiiConn);

        $cache->reinitialize();

        $folder = $this->parsedFolder;
        $cache->writeFolder($folder);

        $file = $this->parsedFile;

        $cache->writeFile($file);

        $stmt = $this->db->query("SELECT * FROM file WHERE logicalPath='/foo/bar' AND ext='md'");
        $record = $stmt->fetchObject();
        self::assertEquals($file->physicalPath, $record->physicalPath);
    }

    #[Test]
    public function can_write_updated_file(): void
    {
        $cache = new PageCacheDB($this->yiiConn);

        $cache->reinitialize();

        $folder = $this->parsedFolder;
        $cache->writeFolder($folder);

        $file = $this->parsedFile;
        $cache->writeFile($file);

        $newFile = new ParsedFile(
            logicalPath: '/foo/bar',
            ext: 'md',
            physicalPath: '/foo/bar.md',
            mtime: 123456,
            title: 'Foobar',
            folder: '/foo',
            order: 2,
            hidden: false,
            routable: true,
            publishDate: new \DateTimeImmutable('2024-10-31'),
            lastModifiedDate: new \DateTimeImmutable('2024-11-01'),
            frontmatter: new BasicPageInformation(),
            summary: '',
            pathName: 'bar',
        );
        $cache->writeFile($newFile);

        $stmt = $this->db->query("SELECT * FROM file WHERE logicalPath='/foo/bar' AND ext='md'");
        $record = $stmt->fetchObject();
        self::assertEquals($newFile->title, $record->title);
    }

//    #[Test]
    public function new_file_must_have_valid_folder(): void
    {
        // @todo This isn't working for some reason. SQL issue of some kind.
        $this->expectException(\PDOException::class);

        $cache = new PageCacheDB($this->yiiConn);

        $cache->reinitialize();

        $file = $this->parsedFile;

        // This should throw.
        $cache->writeFile($file);
    }

    #[Test]
    public function can_read_file(): void
    {
        $cache = new PageCacheDB($this->yiiConn);

        $cache->reinitialize();

        $folder = $this->parsedFolder;
        $cache->writeFolder($folder);

        $file = $this->parsedFile;
        $cache->writeFile($file);

        $savedFile = $cache->readFile('/foo/bar', 'md');

        self::assertEquals($file->physicalPath, $savedFile->physicalPath);
        self::assertEquals($file->mtime, $savedFile->mtime);
        self::assertEquals($file->title, $savedFile->title);
        self::assertEquals($file->publishDate, $savedFile->publishDate);
    }

    #[Test]
    public function returns_null_for_missing_file(): void
    {
        $cache = new PageCacheDB($this->yiiConn);

        $cache->reinitialize();

        $savedFile = $cache->readFile('/beep', 'md');

        self::assertNull($savedFile);
    }

    #[Test]
    public function can_delete_file(): void
    {
        $cache = new PageCacheDB($this->yiiConn);

        $cache->reinitialize();

        $folder = $this->parsedFolder;
        $cache->writeFolder($folder);

        $file = $this->parsedFile;
        $cache->writeFile($file);

        $cache->deleteFile('/foo/bar', 'md');

        $stmt = $this->db->query("SELECT * FROM file WHERE logicalPath='/foo' AND ext='md'");
        $record = $stmt->fetchObject();

        self::assertFalse($record);
    }

    public static function pagination_with_duplicates(): iterable
    {
        $folder = new ParsedFolder(
            '/foo',
            '/foo',
            0,
            false,
            'Foo',
        );

        yield 'basic' => [
            'folder' => $folder,
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/bar.md'),
                self::makeParsedFile(physicalPath: '/foo/bar.latte'),
                self::makeParsedFile(physicalPath: '/foo/baz.latte'),
            ],
            'expectedCount' => 2,
            'limit' => 2,
            'offsets' => [
                // Offset => num expected items on that page.
                0 => 2,
                2 => 0,
            ],
        ];

        yield 'long, no dupes' => [
            'folder' => $folder,
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/a.md'),
                self::makeParsedFile(physicalPath: '/foo/b.latte'),
                self::makeParsedFile(physicalPath: '/foo/c.latte'),
                self::makeParsedFile(physicalPath: '/foo/d.latte'),
                self::makeParsedFile(physicalPath: '/foo/e.md'),
                self::makeParsedFile(physicalPath: '/foo/f.md'),
                self::makeParsedFile(physicalPath: '/foo/g.md'),
            ],
            'expectedCount' => 7,
            'limit' => 2,
            'offsets' => [
                // Offset => num expected items on that page.
                0 => 2,
                2 => 2,
                4 => 2,
                6 => 1,
                8 => 0,
            ],
        ];

        yield 'long, with dupes' => [
            'folder' => $folder,
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/a.md'),
                self::makeParsedFile(physicalPath: '/foo/a.latte'),
                self::makeParsedFile(physicalPath: '/foo/b.latte'),
                self::makeParsedFile(physicalPath: '/foo/c.latte'),
                self::makeParsedFile(physicalPath: '/foo/c.md'),
                self::makeParsedFile(physicalPath: '/foo/d.md'),
                self::makeParsedFile(physicalPath: '/foo/e.md'),
            ],
            'expectedCount' => 5,
            'limit' => 2,
            'offsets' => [
                // Offset => num expected items on that page.
                0 => 2,
                2 => 2,
                4 => 1,
                6 => 0,
            ],
        ];
    }

    /**
     * @param array<ParsedFile> $files
     */
    #[Test, DataProvider('pagination_with_duplicates')]
    public function pagination_readFilesInFolder(ParsedFolder $folder, array $files, int $expectedCount, int $limit, array $offsets, array $tags = []): void
    {
        $cache = new PageCacheDB($this->yiiConn);
        $cache->reinitialize();

        $cache->writeFolder($folder);
        foreach ($files as $f) {
            $cache->writeFile($f);
        }

        // Confirm the overall number of "pages" is what we expect.
        $pageCount = $this->db->query("SELECT COUNT(*) FROM page")->fetchColumn();
        self::assertSame($expectedCount, $pageCount);

        // Paginate through the list and make sure there are the right number of "pages".
        // Because we're not loading the parsed files into Page objects here, we have to
        // duplicate some of that logic.
        foreach ($offsets as $offset => $expectedPageCount) {
            $files = $cache->readFilesInFolder($folder->logicalPath, $limit, $offset);
            $grouped = [];
            foreach ($files as $file) {
                $grouped[$file->logicalPath][] = $file;
            }
            self::assertCount($expectedPageCount, $grouped);
        }
    }

    public static function pagination_with_duplicates_and_tags(): iterable
    {
        $folder = self::makeParsedFolder(physicalPath: '/foo');

        $extraFolder = self::makeParsedFolder(physicalPath: '/bar');

        yield 'basic' => [
            'folder' => $folder,
            'extraFolders' => [$extraFolder],
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/a.md', frontmatter: ['tags' => ['tag1']]),
                self::makeParsedFile(physicalPath: '/foo/a.latte', frontmatter: ['tags' => ['tag1', 'tag2']]),
                self::makeParsedFile(physicalPath: '/foo/b.latte', frontmatter: ['tags' => ['tag1', 'tag2']]),
                self::makeParsedFile(physicalPath: '/foo/c.latte'),
                self::makeParsedFile(physicalPath: '/foo/d.md', frontmatter: ['tags' => ['tag1']]),

                self::makeParsedFile(physicalPath: '/bar/e.md', frontmatter: ['tags' => ['tag1']]),
            ],
            'tags' => ['tag1'],
            'limit' => 2,
            'offsets' => [
                // Offset => num expected items on that page.
                0 => 2,
                2 => 1,
                4 => 0,
            ],
        ];

        yield 'long, no dupes' => [
            'folder' => $folder,
            'extraFolders' => [$extraFolder],
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/a.md', frontmatter: ['tags' => ['tag1']]),
                self::makeParsedFile(physicalPath: '/foo/b.latte'),
                self::makeParsedFile(physicalPath: '/foo/c.latte', frontmatter: ['tags' => ['tag1', 'tag2']]),
                self::makeParsedFile(physicalPath: '/foo/d.latte', frontmatter: ['tags' => ['tag2']]),
                self::makeParsedFile(physicalPath: '/foo/e.md'),
                self::makeParsedFile(physicalPath: '/foo/f.md', frontmatter: ['tags' => ['tag1']]),
                self::makeParsedFile(physicalPath: '/foo/g.md'),

                self::makeParsedFile(physicalPath: '/bar/h.md', frontmatter: ['tags' => ['tag1']]),
            ],
            'tags' => ['tag1'],
            'limit' => 2,
            'offsets' => [
                // Offset => num expected items on that page.
                0 => 2,
                2 => 1,
                4 => 0,
            ],
        ];

        yield 'long, with dupes' => [
            'folder' => $folder,
            'extraFolders' => [$extraFolder],
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/a.md', frontmatter: ['tags' => ['tag1']]),
                self::makeParsedFile(physicalPath: '/foo/a.latte'),
                self::makeParsedFile(physicalPath: '/foo/b.latte', frontmatter: ['tags' => ['tag2']]),
                self::makeParsedFile(physicalPath: '/foo/c.latte', frontmatter: ['tags' => ['tag1']]),
                self::makeParsedFile(physicalPath: '/foo/c.md', frontmatter: ['tags' => ['tag1', 'tag2']]),
                self::makeParsedFile(physicalPath: '/foo/d.md'),
                self::makeParsedFile(physicalPath: '/foo/e.md', frontmatter: ['tags' => ['tag1']]),

                self::makeParsedFile(physicalPath: '/bar/f.md', frontmatter: ['tags' => ['tag1']]),
            ],
            'tags' => ['tag1'],
            'limit' => 2,
            'offsets' => [
                // Offset => num expected items on that page.
                0 => 2,
                2 => 1,
                4 => 0,
            ],
        ];
    }

    /**
     * @param array<ParsedFile> $files
     * @param array<ParsedFolder> $extraFolders
     */
    #[Test, DataProvider('pagination_with_duplicates_and_tags')]
    public function pagination_readFilesInFolderAnyTag(ParsedFolder $folder, array $extraFolders, array $files, int $limit, array $offsets, array $tags): void
    {
        $cache = new PageCacheDB($this->yiiConn);
        $cache->reinitialize();

        $cache->writeFolder($folder);
        foreach ($extraFolders as $f) {
            $cache->writeFolder($f);
        }
        foreach ($files as $f) {
            $cache->writeFile($f);
        }

        // Paginate through the list and make sure there are the right number of "pages".
        // Because we're not loading the parsed files into Page objects here, we have to
        // duplicate some of that logic.
        foreach ($offsets as $offset => $expectedPageCount) {
            $files = $cache->readPagesInFolderAnyTag($folder->logicalPath, $tags, $limit, $offset);
            $grouped = [];
            foreach ($files as $file) {
                $grouped[$file->logicalPath][] = $file;
            }
            self::assertCount($expectedPageCount, $grouped);
        }
    }

    /**
     * I do not like this approach, but I like duplicating the provider even less.
     */
    public static function pagination_with_duplicates_and_tags_all_folders(): iterable
    {
        $cases = iterator_to_array(self::pagination_with_duplicates_and_tags());

        $cases['basic']['offsets'] = [
            // Offset => num expected items on that page.
            0 => 2,
            2 => 2,
            4 => 0,
        ];

        $cases['long, no dupes']['offsets'] = [
            // Offset => num expected items on that page.
            0 => 2,
            2 => 2,
            4 => 0,
        ];
        $cases['long, with dupes']['offsets'] = [
            // Offset => num expected items on that page.
            0 => 2,
            2 => 2,
            4 => 0,
        ];

        return $cases;
    }

    /**
     * @param array<ParsedFile> $files
     * @param array<ParsedFolder> $extraFolders
     */
    #[Test, DataProvider('pagination_with_duplicates_and_tags_all_folders')]
    public function pagination_readFilesAnyTag(ParsedFolder $folder, array $extraFolders, array $files, int $limit, array $offsets, array $tags): void
    {
        $cache = new PageCacheDB($this->yiiConn);
        $cache->reinitialize();

        $cache->writeFolder($folder);
        foreach ($extraFolders as $f) {
            $cache->writeFolder($f);
        }
        foreach ($files as $f) {
            $cache->writeFile($f);
        }

        // Paginate through the list and make sure there are the right number of "pages".
        // Because we're not loading the parsed files into Page objects here, we have to
        // duplicate some of that logic.
        foreach ($offsets as $offset => $expectedPageCount) {
            $files = $cache->readPagesAnyTag($tags, $limit, $offset);
            $grouped = [];
            foreach ($files as $file) {
                $grouped[$file->logicalPath][] = $file;
            }
            self::assertCount($expectedPageCount, $grouped);
        }
    }

    #[Test]
    public function page_count_countPagesInFolder(): void
    {
        $cache = new PageCacheDB($this->yiiConn);
        $cache->reinitialize();

        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/foo'));
        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/bar'));
        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/baz'));
        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/baz/beep'));

        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/a.md'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/b.md'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/b.latte'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/c.md'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/bar/d.md'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/bar/e.md'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/baz/f.md'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/baz/beep/g.md'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/baz/beep/index.md'));

        self::assertSame(3, $cache->countPagesInFolder('/foo'));
        self::assertSame(2, $cache->countPagesInFolder('/bar'));

        // The index file in /baz/beep counts toward the pages in /baz, not /baz/beep
        self::assertSame(2, $cache->countPagesInFolder('/baz'));
        self::assertSame(1, $cache->countPagesInFolder('/baz/beep'));
    }

    #[Test]
    public function page_count_countPages(): void
    {
        $cache = new PageCacheDB($this->yiiConn);
        $cache->reinitialize();

        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/foo'));
        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/bar'));
        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/baz'));
        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/baz/beep'));

        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/a.md'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/b.md'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/b.latte'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/c.md'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/bar/d.md'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/bar/e.md'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/baz/f.md'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/baz/beep/g.md'));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/baz/beep/index.md'));

        self::assertSame(8, $cache->countPages());
    }

    #[Test]
    public function page_count_countPagesAnyTag(): void
    {
        $cache = new PageCacheDB($this->yiiConn);
        $cache->reinitialize();

        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/foo'));
        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/bar'));

        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/a.md', frontmatter: ['tags' => ['tag1', 'tag2']]));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/b.md', frontmatter: ['tags' => ['tag2']]));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/b.latte', frontmatter: ['tags' => ['tag2']]));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/c.md', frontmatter: ['tags' => ['tag1']]));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/bar/d.md', frontmatter: ['tags' => ['tag2']]));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/bar/e.md'));

        self::assertSame(2, $cache->countPagesAnyTag(['tag1']));
        self::assertSame(3, $cache->countPagesAnyTag(['tag2']));
    }

    #[Test]
    public function page_count_countPagesInFolderAnyTag(): void
    {
        $cache = new PageCacheDB($this->yiiConn);
        $cache->reinitialize();

        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/foo'));
        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/bar'));

        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/a.md', frontmatter: ['tags' => ['tag1', 'tag2']]));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/b.md', frontmatter: ['tags' => ['tag2']]));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/b.latte', frontmatter: ['tags' => ['tag2']]));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/foo/c.md', frontmatter: ['tags' => ['tag1']]));

        $cache->writeFile(self::makeParsedFile(physicalPath: '/bar/d.md', frontmatter: ['tags' => ['tag2']]));
        $cache->writeFile(self::makeParsedFile(physicalPath: '/bar/e.md'));

        self::assertSame(2, $cache->countPagesInFolderAnyTag('/foo', ['tag1']));
        self::assertSame(2, $cache->countPagesInFolderAnyTag('/foo', ['tag2']));
        self::assertSame(0, $cache->countPagesInFolderAnyTag('/bar', ['tag1']));
        self::assertSame(1, $cache->countPagesInFolderAnyTag('/bar', ['tag2']));
    }

    private function dumpFilesTable(): void
    {
        var_dump($this->db->query("SELECT logicalPath, physicalPath, folder FROM file")->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function dumpPageView(): void
    {
        var_dump($this->db->query("SELECT * FROM page")->fetchAll(\PDO::FETCH_ASSOC));
    }

}
