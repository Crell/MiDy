<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\FakeFilesystem;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $root = new RootFolder('/', ['/' => $provider]);

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
        $root = new RootFolder('/', ['/' => $provider]);

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
        $root = new RootFolder('/', ['/' => $provider]);

        $dir1 = $root->child('dir1');
        $dir2 = $dir1->child('dir2');

        self::assertTrue($dir2->isDir());
        self::assertEquals('/dir1/dir2', $dir2->urlPath);
        self::assertCount(2, $dir2->children());
    }

    #[Test]
    public function basic_file(): void
    {
        $filePath = $this->makeFilesystemFrom($this->realisticStructure(...))->url();
        $provider = new DirectFileSystemProvider($filePath);
        $root = new RootFolder('/', ['/' => $provider]);

        $file = $root->child('index');

        self::assertTrue($file->isFile());
        self::assertEquals('/index', $file->urlPath);
    }

    #[Test]
    public function sub_file(): void
    {
        $filePath = $this->makeFilesystemFrom($this->realisticStructure(...))->url();
        $provider = new DirectFileSystemProvider($filePath);
        $root = new RootFolder('/', ['/' => $provider]);

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
        $root = new RootFolder('/', [
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
        $root = new RootFolder('/', [
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

    #[Test, DataProvider('findGlobExamplesDirectOnly')]
    public function simple_provider_find_glob(string $pattern, \Closure $check): void
    {
        $filePath = $this->makeFilesystemFrom($this->simpleStructure(...))->url();
        $root = new RootFolder('/', [
            '/' => new DirectFileSystemProvider($filePath),
        ]);

        $pages = $root->find($pattern);

        $check($pages);
    }

    public static function findGlobExamplesDirectOnly(): iterable
    {
        yield 'simple top level file' => [
            'pattern' => 'index.*',
            'check' => function(PageList $pages) {
                self::assertCount(1, $pages);
            },
        ];
        yield 'simple lower-level file' => [
            'pattern' => 'dir1/dir2/subfile1.md',
            'check' => function(PageList $pages) {
                self::assertCount(1, $pages);
            },
        ];
        yield 'multiple files in subdir' => [
            'pattern' => 'dir1/dir2/*',
            'check' => function(PageList $pages) {
                self::assertCount(2, $pages);
            },
        ];
        // Because it indexes by name, this should have only a single result.
        yield 'one name, multiple extensions' => [
            'pattern' => 'dir1/double.*',
            'check' => function(PageList $pages) {
                self::assertCount(1, $pages);
            },
        ];
    }

    #[Test, DataProvider('findGlobExamplesMixedProvider')]
    public function multi_provider_find_glob(string $pattern, \Closure $check): void
    {
        $filePath = $this->makeFilesystemFrom($this->multiProviderSubDirectory(...))->url();
        $root = new RootFolder('/', [
            '/' => new DirectFileSystemProvider($filePath),
            '/ungrouped/' => new DirectFileSystemProvider($filePath),
            '/grouped/' => new FlattenedFileSystemProvider($filePath . '/grouped'),
        ]);

        $pages = $root->find($pattern);

        $check($pages);
    }

    public static function findGlobExamplesMixedProvider(): iterable
    {
        yield 'simple direct files' => [
            'pattern' => 'ungrouped/*',
            'check' => function(PageList $pages) {
                self::assertCount(4, $pages);
            },
        ];
        yield 'simple grouped files' => [
            'pattern' => 'grouped/*',
            'check' => function(PageList $pages) {
                self::assertCount(6, $pages);
            },
        ];
    }


}
