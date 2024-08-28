<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use bovigo\vfs\vfsDirectory;
use bovigo\vfs\vfsStream;
use Crell\MiDy\ClassFinder;
use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\FakeFilesystem;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\TimedCache\FilesystemTimedCache;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RootFolderTest extends TestCase
{
    use FakeFilesystem;

    /**
     * The VFS needs to be static so it's reused, so the require_once() call in the PHP interpreter
     * can not require the "same" file multiple times, leading to double-declaration errors.
     */
    protected static vfsDirectory $vfs;

    #[BeforeClass]
    public static function initFilesystem(): vfsDirectory
    {
        // This mess is because vfsstream doesn't let you create multiple streams
        // at the same time.  Which is dumb.
        $structure = [
            'cache' => [],
            'data' => self::simpleStructure(),
        ];

        return self::$vfs = vfsStream::setup('root', null, $structure);
    }

    protected function makeRootFolder(): RootFolder
    {
        $filePath = self::$vfs->getChild('data')->url();
        $cachePath = self::$vfs->getChild('cache')->url();

        $cache = new FilesystemTimedCache($cachePath);

        $cache->clear();

        return new RootFolder($filePath, $cache, $this->makeFileInterpreter());
    }

    protected function makeFileInterpreter(): FileInterpreter
    {
        $i = new MultiplexedFileInterpreter();
        $i->addInterpreter(new StaticFileInterpreter(new StaticRoutes()));
        $i->addInterpreter(new PhpFileInterpreter(new ClassFinder()));
        $i->addInterpreter(new LatteFileInterpreter());
        $i->addInterpreter(new MarkdownLatteFileInterpreter(new MarkdownPageLoader()));

        return $i;
    }

    #[Test]
    public function count_returns_correct_value(): void
    {
        $r = $this->makeRootFolder();
        self::assertCount(7, $r);
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

        file_put_contents('vfs://root/data/foo.md', 'Foo');

        $page = $r->child('foo.md');

        self::assertEquals('/foo', $page->path());
    }

    #[Test]
    public function can_handle_find_with_extensions(): void
    {
        $r = $this->makeRootFolder();

        file_put_contents('vfs://root/data/bar.md', 'Bar');

        $page = $r->find('/bar.md');

        self::assertEquals('/bar', $page->path());
    }

    #[Test]
    public function folder_displays_as_index_file(): void
    {
        mkdir('vfs://root/data/afolder');
        file_put_contents('vfs://root/data/afolder/index.md', '# Title here');

        $r = $this->makeRootFolder();

        $page = $r->find('/afolder');

        self::assertEquals('/afolder', $page->path());
        self::assertEquals('Title here', $page->title());
    }

    #[Test]
    public function folder_with_no_index_file_displays_own_name(): void
    {
        mkdir('vfs://root/data/somefolder');
        // This should not actually impact anything.
        file_put_contents('vfs://root/data/somefolder/beep.md', '# Title here');

        $r = $this->makeRootFolder();

        $page = $r->find('/somefolder');

        self::assertEquals('/somefolder', $page->path());
        self::assertEquals('Somefolder', $page->title());
    }

    #[Test]
    public function folder_with_order_prefixes_respects_order(): void
    {
        mkdir('vfs://root/data/ordered');
        mkdir('vfs://root/data/ordered/07_a');
        // The "natural" order of files will be the order created, unless the ordering works.
        file_put_contents('vfs://root/data/ordered/05_j.md', '# J');
        file_put_contents('vfs://root/data/ordered/01_z.md', '# Z');
        file_put_contents('vfs://root/data/ordered/07_a/index.md', '# A');
        file_put_contents('vfs://root/data/ordered/02_k.md', '# K');

        $r = $this->makeRootFolder();

        $folder = $r->find('/ordered');

        self::assertInstanceOf(Folder::class, $folder);

        $children = iterator_to_array($folder);

        self::assertEquals('Z', $children[0]->title());
        self::assertEquals('K', $children[1]->title());
        self::assertEquals('J', $children[2]->title());
        self::assertEquals('A', $children[3]->title());

        self::assertEquals('/ordered/z', $children[0]->path());
        self::assertEquals('/ordered/k', $children[1]->path());
        self::assertEquals('/ordered/j', $children[2]->path());
        self::assertEquals('/ordered/a', $children[3]->path());
    }
}
