<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\FakeFilesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PageTreeTest extends TestCase
{
    use FakeFilesystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupFilesystem();
    }

    private function getRootFolder(string $name = 'data'): Folder
    {
        $filePath = $this->usingRoot($name)->url();
        $provider = new DirectFileSystemProvider($filePath);
        return new Folder('/', ['/' => $provider]);
    }

    #[Test]
    public function root_test(): void
    {
        $root = $this->getRootFolder();
        self::assertEquals('Home', $root->title());
        self::assertFalse($root->isFile());
        self::assertTrue($root->isDir());

        self::assertCount(7, $root->children());
    }

    #[Test]
    public function child_directory(): void
    {
        $root = $this->getRootFolder();
        $dir1 = $root->child('dir1');

        self::assertTrue($dir1->isDir());
        self::assertEquals('/dir1', $dir1->urlPath);
        self::assertCount(4, $dir1->children());
    }

    #[Test]
    public function sub_child_directory(): void
    {
        $root = $this->getRootFolder();
        $dir2 = $root->child('dir1')->child('dir2');

        self::assertTrue($dir2->isDir());
        self::assertEquals('/dir1/dir2', $dir2->urlPath);
        self::assertCount(2, $dir2->children());
    }

    #[Test]
    public function basic_file(): void
    {
        $root = $this->getRootFolder();
        $file = $root->child('index');

        self::assertTrue($file->isFile());
        self::assertEquals('/index', $file->urlPath);
    }

    #[Test]
    public function sub_file(): void
    {
        $root = $this->getRootFolder();
        $file = $root->child('dir1')->child('apage');

        self::assertTrue($file->isFile());
        self::assertEquals('/dir1/apage', $file->urlPath);
    }

    #[Test]
    public function nested_provider(): void
    {
        $filePath = $this->usingRoot('nested_provider')->url();
        $root = new Folder('/', [
            '/' => new DirectFileSystemProvider($filePath),
            '/grouped' => new FlattenedFileSystemProvider($filePath . '/grouped'),
        ]);

        $children = $root->children();

        self::assertCount(3, $children);

        $flattenedPath = $root->child('grouped');

        $groupedChildren = $flattenedPath->children();

        self::assertCount(6, $groupedChildren);

    }

}
