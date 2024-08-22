<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use bovigo\vfs\vfsDirectory;
use Crell\MiDy\FakeFilesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RootFolderTest extends TestCase
{
    use FakeFilesystem;

    protected vfsDirectory $vfs;

    protected function initFilesystem(): vfsDirectory
    {
        // This mess is because vfsstream doesn't let you create multiple streams
        // at the same time.  Which is dumb.
        $structure = function () {
            return [
                'cache' => [],
                'data' => $this->simpleStructure(),
            ];
        };

        return $this->vfs = $this->makeFilesystemFrom($structure);
    }

    protected function makeRootFolder(): RootFolder
    {
        $vfs = $this->initFilesystem();
        $filePath = $vfs->getChild('data')->url();
        $cachePath = $vfs->getChild('cache')->url();

        $r = new RootFolder($filePath, new PathCache($cachePath));

        return $r;
    }

    #[Test]
    public function count_returns_correct_value(): void
    {
        $r = $this->makeRootFolder();
        self::assertCount(8, $r);
    }

    #[Test]
    public function correct_child_types(): void
    {
        $r = $this->makeRootFolder();

        foreach ($r as $child) {
            self::assertTrue($child instanceof Page || $child instanceof Folder);
        }
    }

    #[Test]
    public function can_read_specific_page_child(): void
    {
        $r = $this->makeRootFolder();

        $child = $r->child('index');
        self::assertInstanceOf(Page::class, $child);
        self::assertEquals('/index', $child->path());

        $child = $r->child('double');
        self::assertInstanceOf(Page::class, $child);
        self::assertEquals('/double', $child->path());
    }

    #[Test]
    public function can_handle_child_with_extensions(): void
    {
        $r = $this->makeRootFolder();

        file_put_contents('vfs://root/data/foo.txt', 'Foo');

        $page = $r->child('foo.txt');

        self::assertEquals('/foo', $page->path());
    }

    #[Test]
    public function can_handle_find_with_extensions(): void
    {
        $r = $this->makeRootFolder();

        file_put_contents('vfs://root/data/foo.txt', 'Foo');

        $page = $r->find('/foo.txt');

        self::assertEquals('/foo', $page->path());
    }

}
