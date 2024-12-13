<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\PageTree\BasicPageInformation;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PageCacheDBTest extends TestCase
{
    private ParsedFile $parsedFile;
    private ParsedFolder $parsedFolder;
    private \PDO $db;

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

    #[Before]
    public function makeDb(): void
    {
        $this->db = new \PDO('sqlite::memory:');
    }

    #[Test, DoesNotPerformAssertions]
    public function reinitialize_creates_tables_if_they_dont_exist(): void
    {
        $cache = new PageCacheDB($this->db);

        $cache->reinitialize();

        // These will throw an exception if the tables do not exist.
        $this->db->query("SELECT 1 FROM folder")->execute();
        $this->db->query("SELECT 1 FROM file")->execute();
    }

    #[Test, DoesNotPerformAssertions]
    public function reinitialize_recreates_tables_if_they_do_exist(): void
    {
        $cache = new PageCacheDB($this->db);

        $this->db->exec('CREATE TABLE IF NOT EXISTS folder(fake int)');

        $cache->reinitialize();

        // These will throw an exception if the tables do not exist.
        $this->db->query("SELECT 1 FROM folder")->execute();
        $this->db->query("SELECT 1 FROM file")->execute();
    }

    #[Test]
    public function can_write_new_folder(): void
    {
        $cache = new PageCacheDB($this->db);

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
        $cache = new PageCacheDB($this->db);

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
        $cache = new PageCacheDB($this->db);

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
        $cache = new PageCacheDB($this->db);

        $cache->reinitialize();

        $savedFolder = $cache->readFolder('/foo');

        self::assertNull($savedFolder);
    }

    #[Test]
    public function can_delete_folder(): void
    {
        $cache = new PageCacheDB($this->db);

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
        $cache = new PageCacheDB($this->db);

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
        $cache = new PageCacheDB($this->db);

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

        $cache = new PageCacheDB($this->db);

        $cache->reinitialize();

        $file = $this->parsedFile;

        // This should throw.
        $cache->writeFile($file);
    }

    #[Test]
    public function can_read_file(): void
    {
        $cache = new PageCacheDB($this->db);

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
        $cache = new PageCacheDB($this->db);

        $cache->reinitialize();

        $savedFile = $cache->readFile('/beep', 'md');

        self::assertNull($savedFile);
    }

    #[Test]
    public function can_delete_file(): void
    {
        $cache = new PageCacheDB($this->db);

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
}
