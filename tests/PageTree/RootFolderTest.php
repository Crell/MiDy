<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\RootFilesystemSetup;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RootFolderTest extends TestCase
{
    use RootFilesystemSetup;

    #[Test]
    public function count_returns_correct_value(): void
    {
        $r = $this->makeRootFolder();
        self::assertCount(6, $r);
    }

    #[Test]
    public function correct_child_types(): void
    {
        $r = $this->makeRootFolder();

        foreach ($r as $child) {
            self::assertInstanceOf(Page::class, $child);
        }
    }

    #[Test]
    public function can_read_specific_page_child(): void
    {
        $r = $this->makeRootFolder();

        $child = $r->indexPage();
        self::assertInstanceOf(Page::class, $child);
        self::assertEquals('/index', $child->path());

        $child = $r->get('double');
        self::assertInstanceOf(Page::class, $child);
        self::assertEquals('/double', $child->path());
    }

    #[Test]
    public function can_handle_child_with_extensions(): void
    {
        $r = $this->makeRootFolder();

        file_put_contents('vfs://root/data/foo.md', 'Foo');

        $page = $r->get('foo.md');

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

        // array_values() so that we can examine them by order, not by name index.
        $children = array_values(iterator_to_array($folder));

        self::assertEquals('Z', $children[0]->title());
        self::assertEquals('K', $children[1]->title());
        self::assertEquals('J', $children[2]->title());
        self::assertEquals('A', $children[3]->title());

        self::assertEquals('/ordered/z', $children[0]->path());
        self::assertEquals('/ordered/k', $children[1]->path());
        self::assertEquals('/ordered/j', $children[2]->path());
        self::assertEquals('/ordered/a', $children[3]->path());
    }

    #[Test]
    public function folder_with_date_prefixes_respects_order(): void
    {
        mkdir('vfs://root/data/dated');
        mkdir('vfs://root/data/dated/2024-07-01_a');
        // The "natural" order of files will be the order created, unless the ordering works.
        file_put_contents('vfs://root/data/dated/2024-05-01_j.md', '# J');
        file_put_contents('vfs://root/data/dated/2024-01-01_z.md', '# Z');
        file_put_contents('vfs://root/data/dated/2024-07-01_a/index.md', '# A');
        file_put_contents('vfs://root/data/dated/2024-02-01_k.md', '# K');

        $r = $this->makeRootFolder();

        $folder = $r->find('/dated');

        self::assertInstanceOf(Folder::class, $folder);

        // array_values() so that we can examine them by order, not by name index.
        $children = array_values(iterator_to_array($folder));

        self::assertEquals('Z', $children[0]->title());
        self::assertEquals('K', $children[1]->title());
        self::assertEquals('J', $children[2]->title());
        self::assertEquals('A', $children[3]->title());

        self::assertEquals('/dated/z', $children[0]->path());
        self::assertEquals('/dated/k', $children[1]->path());
        self::assertEquals('/dated/j', $children[2]->path());
        self::assertEquals('/dated/a', $children[3]->path());
    }

    #[Test]
    public function folder_with_order_prefixes_respects_order_reversed(): void
    {
        mkdir('vfs://root/data/reversed');
        mkdir('vfs://root/data/reversed/07_a');
        // The "natural" order of files will be the order created, unless the ordering works.
        file_put_contents('vfs://root/data/reversed/05_j.md', '# J');
        file_put_contents('vfs://root/data/reversed/01_z.md', '# Z');
        file_put_contents('vfs://root/data/reversed/07_a/index.md', '# A');
        file_put_contents('vfs://root/data/reversed/02_k.md', '# K');
        file_put_contents('vfs://root/data/reversed/folder.midy', '{"order":"desc"}');

        $r = $this->makeRootFolder();

        $folder = $r->find('/reversed');

        self::assertInstanceOf(Folder::class, $folder);

        // array_values() so that we can examine them by order, not by name index.
        $children = array_values(iterator_to_array($folder));

        self::assertEquals('Z', $children[3]->title());
        self::assertEquals('K', $children[2]->title());
        self::assertEquals('J', $children[1]->title());
        self::assertEquals('A', $children[0]->title());

        self::assertEquals('/reversed/z', $children[3]->path());
        self::assertEquals('/reversed/k', $children[2]->path());
        self::assertEquals('/reversed/j', $children[1]->path());
        self::assertEquals('/reversed/a', $children[0]->path());
    }

    #[Test]
    public function folder_with_flattening_renders_correctly(): void
    {
        mkdir('vfs://root/data/flattened');
        // The directories will be listed in the order created. That may or may not
        // cause an issue, depending on use case.  See @todo in Folder.
        mkdir('vfs://root/data/flattened/2022');
        mkdir('vfs://root/data/flattened/2023');
        mkdir('vfs://root/data/flattened/2024');
        file_put_contents('vfs://root/data/flattened/folder.midy', '{"flatten": true}');
        file_put_contents('vfs://root/data/flattened/2024/e.md', '# E');
        file_put_contents('vfs://root/data/flattened/2024/f.md', '# F');
        file_put_contents('vfs://root/data/flattened/2022/a.md', '# A');
        file_put_contents('vfs://root/data/flattened/2022/b.md', '# B');
        file_put_contents('vfs://root/data/flattened/2023/c.md', '# C');
        file_put_contents('vfs://root/data/flattened/2023/d.md', '# D');

        $r = $this->makeRootFolder();

        $folder = $r->find('/flattened');

        self::assertInstanceOf(Folder::class, $folder);

        // array_values() so that we can examine them by order, not by name index.
        $children = array_values(iterator_to_array($folder));

        self::assertEquals('A', $children[0]->title());
        self::assertEquals('B', $children[1]->title());
        self::assertEquals('C', $children[2]->title());
        self::assertEquals('D', $children[3]->title());
        self::assertEquals('E', $children[4]->title());
        self::assertEquals('F', $children[5]->title());

        self::assertEquals('/flattened/a', $children[0]->path());
        self::assertEquals('/flattened/b', $children[1]->path());
        self::assertEquals('/flattened/c', $children[2]->path());
        self::assertEquals('/flattened/d', $children[3]->path());
        self::assertEquals('/flattened/e', $children[4]->path());
        self::assertEquals('/flattened/f', $children[5]->path());
    }

    #[Test]
    public function folder_with_flattening_and_reversed_renders_correctly(): void
    {
        mkdir('vfs://root/data/flatreversed');
        // The directories will be listed in the order created. That may or may not
        // cause an issue, depending on use case.  See @todo in Folder.
        mkdir('vfs://root/data/flatreversed/2022');
        mkdir('vfs://root/data/flatreversed/2023');
        mkdir('vfs://root/data/flatreversed/2024');
        file_put_contents('vfs://root/data/flatreversed/folder.midy', '{"flatten": true, "order": "desc"}');
        file_put_contents('vfs://root/data/flatreversed/2024/2024-05-01_e.md', '# E');
        file_put_contents('vfs://root/data/flatreversed/2024/2024-06-01_f.md', '# F');
        file_put_contents('vfs://root/data/flatreversed/2022/2022-02-01_a.md', '# A');
        file_put_contents('vfs://root/data/flatreversed/2022/2022-08-01_b.md', '# B');
        file_put_contents('vfs://root/data/flatreversed/2023/2023-04-01_c.md', '# C');
        file_put_contents('vfs://root/data/flatreversed/2023/2023-09-01_d.md', '# D');

        $r = $this->makeRootFolder();

        $folder = $r->find('/flatreversed');

        self::assertInstanceOf(Folder::class, $folder);

        // array_values() so that we can examine them by order, not by name index.
        $children = array_values(iterator_to_array($folder));

        self::assertEquals('A', $children[5]->title());
        self::assertEquals('B', $children[4]->title());
        self::assertEquals('C', $children[3]->title());
        self::assertEquals('D', $children[2]->title());
        self::assertEquals('E', $children[1]->title());
        self::assertEquals('F', $children[0]->title());

        self::assertEquals('/flatreversed/a', $children[5]->path());
        self::assertEquals('/flatreversed/b', $children[4]->path());
        self::assertEquals('/flatreversed/c', $children[3]->path());
        self::assertEquals('/flatreversed/d', $children[2]->path());
        self::assertEquals('/flatreversed/e', $children[1]->path());
        self::assertEquals('/flatreversed/f', $children[0]->path());
    }

    #[Test]
    public function folder_listing_excludes_index_file(): void
    {
        mkdir('vfs://root/data/hideindex');
        file_put_contents('vfs://root/data/hideindex/index.md', '# Title here');
        file_put_contents('vfs://root/data/hideindex/a.md', '# A');
        file_put_contents('vfs://root/data/hideindex/b.md', '# B');

        $r = $this->makeRootFolder();

        $page = $r->find('/hideindex');

        self::assertNotNull($page);

        // array_values() so that we can examine them by order, not by name index.
        $children = array_values(iterator_to_array($page));

        self::assertCount(2, $children);
    }

    #[Test]
    public function folder_can_be_hiden(): void
    {
        mkdir('vfs://root/data/hidefolder');
        mkdir('vfs://root/data/hidefolder/hidden');
        file_put_contents('vfs://root/data/hidefolder/index.md', '# Title here');
        file_put_contents('vfs://root/data/hidefolder/a.md', '# A');
        file_put_contents('vfs://root/data/hidefolder/b.md', '# B');
        file_put_contents('vfs://root/data/hidefolder/hidden/folder.midy', '{"hidden": true}');

        $r = $this->makeRootFolder();

        $page = $r->find('/hidefolder');

        $children = iterator_to_array($page);

        self::assertCount(2, $children);
    }
}
