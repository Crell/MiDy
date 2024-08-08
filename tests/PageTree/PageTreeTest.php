<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\FakeFilesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PageTreeTest extends TestCase
{
    use FakeFilesystem;

    #[Test]
    public function root_test(): void
    {
        $filePath = $this->makeFilesystemFrom($this->realisticStructure(...))->url();
        $provider = new DirectFileSystemProvider($filePath);
        $root = new Folder('/', ['/' => $provider]);

        self::assertEquals('Home', $root->title());
        self::assertFalse($root->isFile());
        self::assertTrue($root->isDir());

        self::assertCount(7, $root->children());
    }

    #[Test]
    public function child_directory(): void
    {
        $filePath = $this->makeFilesystemFrom($this->realisticStructure(...))->url();
        $provider = new DirectFileSystemProvider($filePath);
        $root = new Folder('/', ['/' => $provider]);

        $dir1 = $root->child('dir1');

        self::assertTrue($dir1->isDir());
        self::assertEquals('/dir1', $dir1->urlPath);
        self::assertCount(4, $dir1->children());
    }

    #[Test]
    public function sub_child_directory(): void
    {
        $filePath = $this->makeFilesystemFrom($this->realisticStructure(...))->url();
        $provider = new DirectFileSystemProvider($filePath);
        $root = new Folder('/', ['/' => $provider]);

        $dir2 = $root->child('dir1')->child('dir2');

        self::assertTrue($dir2->isDir());
        self::assertEquals('/dir1/dir2', $dir2->urlPath);
        self::assertCount(2, $dir2->children());
    }

    #[Test]
    public function basic_file(): void
    {
        $filePath = $this->makeFilesystemFrom($this->realisticStructure(...))->url();
        $provider = new DirectFileSystemProvider($filePath);
        $root = new Folder('/', ['/' => $provider]);

        $file = $root->child('index');

        self::assertTrue($file->isFile());
        self::assertEquals('/index', $file->urlPath);
    }

    #[Test]
    public function sub_file(): void
    {
        $filePath = $this->makeFilesystemFrom($this->realisticStructure(...))->url();
        $provider = new DirectFileSystemProvider($filePath);
        $root = new Folder('/', ['/' => $provider]);

        $file = $root->child('dir1')->child('apage');

        self::assertTrue($file->isFile());
        self::assertEquals('/dir1/apage', $file->urlPath);
    }

    private function nestedProviders(): array
    {
        return [
            'a.txt.' => '',
            'b.txt.' => '',
            'grouped' => [
                '2021' => [
                    'a.md' => '',
                    'b.md' => '',
                    'c.md' => '',
                ],
                '2022' => [
                    'd.md' => '',
                    'e.md' => '',
                    'f.md' => '',
                ],
            ],
        ];
    }

    #[Test]
    public function nested_provider(): void
    {
        $filePath = $this->makeFilesystemFrom($this->nestedProviders(...))->url();
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

    #[Test]
    public function multi_provider_sub_directory(): void
    {
        $filePath = $this->makeFilesystemFrom($this->multiProviderSubDirectory(...))->url();
        $root = new Folder('/', [
            '/' => new DirectFileSystemProvider($filePath),
            '/ungrouped' => new DirectFileSystemProvider($filePath),
            '/grouped' => new FlattenedFileSystemProvider($filePath . '/grouped'),
        ]);

        $children = $root->children();

        self::assertCount(2, $children);

        $groupedChildren = $root->child('grouped')->children();
        self::assertCount(6, $groupedChildren);

        $ungroupedChildren = $root->child('ungrouped')->children();
        self::assertCount(4, $ungroupedChildren);
    }

    private function multiProviderSubDirectory(): array
    {
        return [
            'grouped' => [
                '2021' => [
                    'a.md' => '',
                    'b.md' => '',
                    'c.md' => '',
                ],
                '2022' => [
                    'd.md' => '',
                    'e.md' => '',
                    'f.md' => '',
                ],
            ],
            'ungrouped' => [
                'a.md' => '',
                'b.md' => '',
                'c.md' => '',
                'd.md' => '',
            ],
        ];
    }

}
