<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\SetupFilesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PageRepoTest extends TestCase
{
    use SetupFilesystem;
    use SetupDB;
    use MakerUtils;


    #[Test, DoesNotPerformAssertions]
    public function reinitialize_creates_tables_if_they_dont_exist(): void
    {
        $cache = new PageRepo($this->yiiConn);

        $cache->reinitialize();

        // These will throw an exception if the tables do not exist.
        $this->db->query("SELECT 1 FROM folder")->execute();
        $this->db->query("SELECT 1 FROM page")->execute();
    }

    #[Test, DoesNotPerformAssertions]
    public function reinitialize_recreates_tables_if_they_do_exist(): void
    {
        $cache = new PageRepo($this->yiiConn);

        $this->db->exec('CREATE TABLE IF NOT EXISTS folder(fake int)');

        $cache->reinitialize();

        // These will throw an exception if the tables do not exist.
        $this->db->query("SELECT 1 FROM folder")->execute();
        $this->db->query("SELECT 1 FROM page")->execute();
    }

    #[Test]
    public function can_write_new_folder(): void
    {
        $cache = new PageRepo($this->yiiConn);

        $cache->reinitialize();

        $folder = self::makeParsedFolder(physicalPath: '/foo');

        $cache->writeFolder($folder);

        $stmt = $this->db->query("SELECT * FROM folder WHERE logicalPath='/foo'");
        $record = $stmt->fetchObject();
        self::assertEquals($folder->physicalPath, $record->physicalPath);
    }

    #[Test]
    public function can_write_updated_folder(): void
    {
        $cache = new PageRepo($this->yiiConn);

        $cache->reinitialize();

        $folder = self::makeParsedFolder(physicalPath: '/foo');
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
        $cache = new PageRepo($this->yiiConn);

        $cache->reinitialize();

        $folder = self::makeParsedFolder(physicalPath: '/foo');
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
        $cache = new PageRepo($this->yiiConn);

        $cache->reinitialize();

        $savedFolder = $cache->readFolder('/foo');

        self::assertNull($savedFolder);
    }

    #[Test]
    public function can_delete_folder(): void
    {
        $cache = new PageRepo($this->yiiConn);
        $cache->reinitialize();

        $folder = self::makeParsedFolder(physicalPath: '/foo');
        $cache->writeFolder($folder);

        $cache->deleteFolder('/foo');

        $stmt = $this->db->query("SELECT * FROM folder WHERE logicalPath='/foo'");
        $record = $stmt->fetchObject();

        self::assertFalse($record);
    }

    public static function page_data(): iterable
    {
        yield 'single file page' => [
            'folder' => self::makeParsedFolder(physicalPath: '/foo'),
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/test.md'),
            ],
            'pagePath' => '/foo/test',
            'dbValidation' => function (self $test) {
                $page = $test->getPage('/foo/test');
                self::assertFalse((bool)$page['hidden']);
            },
            'validation' => function (PageRecord $page) {
                foreach ($page->files as $file) {
                    self::assertFalse($file->isFolder);
                    self::assertFalse($file->hidden);
                }
                self::assertCount(1, $page->files);
            },
        ];

        yield 'multi-file page' => [
            'folder' => self::makeParsedFolder(physicalPath: '/foo'),
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/test.md'),
                self::makeParsedFile(physicalPath: '/foo/test.latte'),
            ],
            'pagePath' => '/foo/test',
            'dbValidation' => function (self $test) {
                $page = $test->getPage('/foo/test');
                self::assertFalse((bool)$page['hidden']);
            },
            'validation' => function (PageRecord $page) {
                foreach ($page->files as $file) {
                    self::assertFalse($file->isFolder);
                    self::assertFalse($file->hidden);
                }
                self::assertCount(2, $page->files);
            },
        ];

        yield 'multi-file page, some hidden' => [
            'folder' => self::makeParsedFolder(physicalPath: '/foo'),
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/test.md'),
                self::makeParsedFile(physicalPath: '/foo/test.latte'),
                self::makeParsedFile(physicalPath: '/foo/test.gif', hidden: true),
            ],
            'pagePath' => '/foo/test',
            'dbValidation' => function (self $test) {
                $page = $test->getPage('/foo/test');
                self::assertFalse((bool)$page['hidden']);
            },
            'validation' => function (PageRecord $page) {
                foreach ($page->files as $file) {
                    self::assertFalse($file->isFolder);
                }
                self::assertCount(3, $page->files);
            },
        ];

        yield 'multi-file page, all hidden' => [
            'folder' => self::makeParsedFolder(physicalPath: '/foo'),
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/test.md', hidden: true),
                self::makeParsedFile(physicalPath: '/foo/test.latte', hidden: true),
                self::makeParsedFile(physicalPath: '/foo/test.gif', hidden: true),
            ],
            'pagePath' => '/foo/test',
            'dbValidation' => function (self $test) {
                $page = $test->getPage('/foo/test');
                self::assertTrue((bool)$page['hidden']);
            },
            'validation' => function (PageRecord $page) {
                foreach ($page->files as $file) {
                    self::assertFalse($file->isFolder);
                }
                self::assertCount(3, $page->files);
            },
        ];
    }

    #[Test, DataProvider('page_data')]
    public function page_save_and_load(ParsedFolder $folder, array $files, string $pagePath, \Closure $dbValidation, \Closure $validation): void
    {
        $cache = new PageRepo($this->yiiConn);
        $cache->reinitialize();

        $cache->writeFolder($folder);

        $page = new PageRecord($pagePath, $folder->logicalPath, $files);
        $cache->writePage($page);

        $dbValidation($this);

        $record = $cache->readPageFiles($pagePath);

        self::assertEquals('/foo/test', $page->logicalPath);
        self::assertEquals('/foo', $page->folder);
        foreach ($page->files as $file) {
            self::assertEquals($pagePath, $file->logicalPath);
        }

        $validation($record);
    }

    private function getPage(string $path): array
    {
        return $this->yiiConn
            ->createCommand("SELECT * FROM page WHERE logicalPath=:logicalPath")
            ->bindParam(':logicalPath', $path)
            ->queryOne();
    }

    private function dumpPageView(): void
    {
        var_dump($this->yiiConn->createCommand("SELECT * FROM page")->queryAll());
    }

}
