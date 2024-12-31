<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\PageTreeDB2\Parser\LatteFileParser;
use Crell\MiDy\PageTreeDB2\Parser\MarkdownLatteFileParser;
use Crell\MiDy\PageTreeDB2\Parser\MultiplexedFileParser;
use Crell\MiDy\PageTreeDB2\Parser\Parser;
use Crell\MiDy\PageTreeDB2\Parser\PhpFileParser;
use Crell\MiDy\PageTreeDB2\Parser\StaticFileParser;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests that use the VFS need to run in their own separate processes.
 *
 * Otherwise, if a .php file is parsed, it will result in double-declaration
 * of the class, and a fatal error.
 *
 * However! Any test run in its own process won't work with xdebug, even though
 * phpunit process isolation is disabled.  I have no idea why.  So instead,
 * we flag each test individually to be in its own process, so we can selectively
 * disable that for debugging.  This makes no sense and needs to be fixed.
 */
class PageTreeTest extends TestCase
{
    use SetupCache;

    protected vfsStreamDirectory $vfs;

    private Parser $parser;

    #[Before]
    public function setupParser(): void
    {
        $fileParser = new MultiplexedFileParser();
        $fileParser->addParser(new StaticFileParser(new StaticRoutes()));
        $fileParser->addParser(new PhpFileParser());
        $fileParser->addParser(new LatteFileParser());
        $fileParser->addParser(new MarkdownLatteFileParser(new MarkdownPageLoader()));

        $this->parser = new Parser($this->setupCache(), $fileParser);
    }

    #[Before]
    public function initFilesystem(): vfsStreamDirectory
    {
        // This mess is because vfsstream doesn't let you create multiple streams
        // at the same time.  Which is dumb.
        $structure = [
            'cache' => [],
            'routes' => [],
        ];

        return $this->vfs = vfsStream::setup('root', null, $structure);
    }

    #[Test, RunInSeparateProcess]
    public function can_lazy_load_folder(): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        file_put_contents($routesPath . '/foo.html', 'Foo');
        file_put_contents($routesPath . '/bar.html', 'Bar');
        file_put_contents($routesPath . '/folder.midy', '{"order": "Desc"}');
        mkdir($routesPath . '/subdir');

        $tree = new PageTree($this->cache, $this->parser, $routesPath);

        $folder = $tree->folder('/subdir');

        self::assertEquals('/subdir', $folder->logicalPath);
    }

    #[Test, RunInSeparateProcess]
    public function can_instantiate_pages(): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        file_put_contents($routesPath . '/single.html', 'Single');
        file_put_contents($routesPath . '/double.html', 'Double, HTML');
        file_put_contents($routesPath . '/double.css', 'Double, CSS');
        file_put_contents($routesPath . '/folder.midy', '{"order": "Desc"}');
        mkdir($routesPath . '/subdir');

        $tree = new PageTree($this->cache, $this->parser, $routesPath);

        $folder = $tree->folder('/');

        self::assertCount(2, $folder);
    }

    #[Test, RunInSeparateProcess]
    public function can_instantiate_supported_file_types(): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        file_put_contents($routesPath . '/static.html', 'Foo');
        file_put_contents($routesPath . '/markdown.md', 'Bar');
        file_put_contents($routesPath . '/latte.latte', 'Bar');
        file_put_contents($routesPath . '/php.php', '<?php class Test {}');

        $tree = new PageTree($this->cache, $this->parser, $routesPath);

        $folder = $tree->folder('/');

        self::assertCount(4, $folder);
    }

    #[Test, RunInSeparateProcess]
    public function can_iterate_children(): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        file_put_contents($routesPath . '/static.html', 'Foo');
        file_put_contents($routesPath . '/markdown.md', 'Bar');
        file_put_contents($routesPath . '/latte.latte', 'Bar');
        file_put_contents($routesPath . '/php.php', '<?php class Test {}');

        $tree = new PageTree($this->cache, $this->parser, $routesPath);

        $folder = $tree->folder('/');

        self::assertCount(4, $folder);
        foreach ($folder as $page) {
            self::assertInstanceOf(PageFile::class, $page);
        }
    }

    #[Test, RunInSeparateProcess]
    public function out_of_date_file_is_reloaded(): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        $filename = $routesPath . '/foo.md';

        // Since the test runs in nanoseconds, we need to force the
        // mtime (measured in seconds) to be in the past.
        // We need to clear the stat cache so the updated mtime will get noticed.
        file_put_contents($filename, '# Old');
        touch($filename, strtotime('-10 min'));
        clearstatcache(true, $filename);

        $tree = new PageTree($this->cache, $this->parser, $routesPath);

        $folder = $tree->folder('/');

        self::assertEquals('Old', $folder->get('foo')->title);

        // Update the file.
        file_put_contents($filename, '# New');
        clearstatcache(true, $filename);

        // The folder caches its children, so we cannot reuse the same object.
        $folder = $tree->folder('/');
        self::assertEquals('New', $folder->get('foo')->title);
    }

    #[Test, RunInSeparateProcess]
    public function out_of_date_folder_is_reloaded(): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        $firstfile = $routesPath . '/foo.md';
        $newfile = $routesPath . '/bar.md';

        // Since the test runs in nanoseconds, we need to force the
        // mtime (measured in seconds) to be in the past.
        // We need to clear the stat cache so the updated mtime will get noticed.
        file_put_contents($firstfile, '# First');
        touch($routesPath, strtotime('-10 min'));
        clearstatcache(true, $routesPath);

        $tree = new PageTree($this->cache, $this->parser, $routesPath);

        $folder = $tree->folder('/');

        self::assertCount(1, $folder);

        // Add another file to the folder.
        file_put_contents($newfile, '# New');
        clearstatcache(true, $routesPath);

        // We need a new folder to ensure we get fresh data.
        $folder = $tree->folder('/');
        self::assertCount(2, $folder);
        self::assertEquals('First', $folder->get('foo')->title);
    }

    #[Test, RunInSeparateProcess]
    public function index_files_in_root_dont_count_toward_child_count(): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        file_put_contents($routesPath . '/first.md', '# First');
        file_put_contents($routesPath . '/second.md', '# Second');
        file_put_contents($routesPath . '/index.md', '# Index');

        $tree = new PageTree($this->cache, $this->parser, $routesPath);

        $folder = $tree->folder('/');

        // The index file in "self" should not count as a child
        self::assertCount(2, $folder);
    }

    #[Test, RunInSeparateProcess]
    public function index_files_in_subdir_count_toward_parent_count(): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        mkdir($routesPath . '/sub');
        file_put_contents($routesPath . '/first.md', '# First');
        file_put_contents($routesPath . '/second.md', '# Second');
        file_put_contents($routesPath . '/index.md', '# Index');
        file_put_contents($routesPath . '/sub/child1.md', '# Child 1');
        file_put_contents($routesPath . '/sub/child2.md', '# Child 2');
        file_put_contents($routesPath . '/sub/index.md', '# Child Index');

        $tree = new PageTree($this->cache, $this->parser, $routesPath);

        $folder = $tree->folder('/');

        // The index file in "self" should not count as a child,
        // but the sub/index page should.
        self::assertCount(3, $folder);
    }

    #[Test, RunInSeparateProcess]
    public function can_access_hidden_folder_directly(): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        // sub has no index, so it won't be shown as a child of root.
        // But we should still be able to access it if we know it's there.

        mkdir($routesPath . '/sub');
        file_put_contents($routesPath . '/first.md', '# First');
        file_put_contents($routesPath . '/second.md', '# Second');
        file_put_contents($routesPath . '/sub/child1.md', '# Child 1');
        file_put_contents($routesPath . '/sub/child2.md', '# Child 2');

        $tree = new PageTree($this->cache, $this->parser, $routesPath);

        $folder = $tree->folder('/sub');

        self::assertCount(2, $folder);
    }

    #[Test, RunInSeparateProcess]
    public function multiple_mount_points_merge_cleanly_if_direct_child(): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        $rootPath = $routesPath;
        $adminPath = $routesPath . '/adminPages';
        mkdir($adminPath);

        file_put_contents($rootPath . '/first.md', '# First');
        file_put_contents($rootPath . '/second.md', '# Second');
        file_put_contents($adminPath . '/child1.md', '# Admin 1');
        file_put_contents($adminPath . '/child2.md', '# Admin 2');
        file_put_contents($adminPath . '/index.md', '# Admin Index');

        $tree = new PageTree($this->cache, $this->parser, $routesPath);
        $tree->mount($adminPath, '/admin');

        // Two files and the subdir, which is a mount.
        $folder = $tree->folder('/');
        self::assertCount(3, $folder);

        $folder = $tree->folder('/admin');
        self::assertCount(2, $folder);
    }

    #[Test, RunInSeparateProcess]
    public function multiple_mount_points_merge_cleanly_if_deep_child(): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        $rootPath = $routesPath;
        $adminPath = $routesPath . '/adminPages';
        mkdir($adminPath);
        mkdir($rootPath . '/admin');

        file_put_contents($rootPath . '/first.md', '# First');
        file_put_contents($rootPath . '/second.md', '# Second');
        file_put_contents($adminPath . '/child1.md', '# Admin 1');
        file_put_contents($adminPath . '/child2.md', '# Admin 2');
        file_put_contents($adminPath . '/index.md', '# Admin Index');

        $tree = new PageTree($this->cache, $this->parser, $routesPath);
        $tree->mount($adminPath, '/admin/sub');


        // Two files, but the /admin folder has no files in it
        // (because adminPages is mounted to /admin/sub), so
        // the directory doesn't show.
        $folder = $tree->folder('/');
        self::assertCount(2, $folder);

        $folder = $tree->folder('/admin/sub');
        self::assertCount(2, $folder);

        // Just the subdir.
        $folder = $tree->folder('/admin');
        self::assertCount(1, $folder);
    }

    #[Test, RunInSeparateProcess]
    public function deep_reindex_finds_all_files(): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        $rootPath = $routesPath;
        $adminPath = $routesPath . '/adminPages';
        mkdir($adminPath);
        mkdir($rootPath . '/admin');
        mkdir($rootPath . '/sub');
        mkdir($rootPath . '/sub/sub');
        mkdir($rootPath . '/sub/sub/sub');

        file_put_contents($rootPath . '/first.md', '# First');
        file_put_contents($rootPath . '/second.md', '# Second');
        file_put_contents($rootPath . '/sub/sub1.md', '# Child 1');
        file_put_contents($rootPath . '/sub/sub2.md', '# Child 2');
        file_put_contents($rootPath . '/sub/sub/grandchild1.md', '# Grandchild 1');
        file_put_contents($rootPath . '/sub/sub/grandchild2.md', '# Grandchild 2');
        file_put_contents($rootPath . '/sub/sub/index.md', '# Grandchild Index');
        file_put_contents($adminPath . '/child1.md', '# Admin 1');
        file_put_contents($adminPath . '/child2.md', '# Admin 2');
        file_put_contents($adminPath . '/index.md', '# Admin Index');

        $tree = new PageTree($this->cache, $this->parser, $routesPath);
        $tree->mount($adminPath, '/admin');

        $tree->reindexAll();

        // Every file above should be found here as a page.
        $stmt = $this->db->query("SELECT COUNT(*) FROM file");
        $count = $stmt->fetchColumn();
        self::assertEquals(10, $count);
    }

    #[Test, RunInSeparateProcess]
    public function tags_are_indexed(): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        file_put_contents($routesPath . '/first.md', <<<END
        ---
        title: First
        tags: [first, page]
        ---
        First page
        END);
        file_put_contents($routesPath . '/second.md', <<<END
        ---
        title: Second
        tags: [second, page]
        ---
        Second page
        END);

        $tree = new PageTree($this->cache, $this->parser, $routesPath);

        $tree->reindexAll();

        $stmt = $this->db->query("SELECT COUNT(*) FROM file_tag");
        $count = $stmt->fetchColumn();
        self::assertEquals(4, $count);
        $stmt = $this->db->query("SELECT DISTINCT tag FROM file_tag");
        $records = $stmt->fetchAll();
        self::assertCount(3, $records);

        $page = $tree->page('/first');
        self::assertEquals(['first', 'page'], $page->tags);
    }

    public static function limitProvider(): iterable
    {
        yield '3 pages, show 2' => [
            'files' => [
                '/page1.md' => '# Page 1',
                '/page2.md' => '# Page 2',
                '/page3.md' => '# Page 3',
            ],
            'limit' => 2,
            'offset' => 0,
            'expected' => 2,
        ];

        yield '3 pages, show 4' => [
            'files' => [
                '/page1.md' => '# Page 1',
                '/page2.md' => '# Page 2',
                '/page3.md' => '# Page 3',
            ],
            'limit' => 4,
            'offset' => 0,
            'expected' => 3,
        ];

        yield 'with extra route files, limited' => [
            'files' => [
                '/page1.md' => '# Page 1',
                '/page2.md' => '# Page 2',
                '/page2.latte' => 'Page 2',
                '/page3.md' => '# Page 3',
            ],
            'limit' => 2,
            'offset' => 0,
            'expected' => 2,
        ];

        yield 'with extra route files, not limited' => [
            'files' => [
                '/page1.md' => '# Page 1',
                '/page2.md' => '# Page 2',
                '/page2.latte' => 'Page 2',
                '/page3.md' => '# Page 3',
            ],
            'limit' => 4,
            'offset' => 0,
            'expected' => 3,
        ];

        yield 'with an index file, first' => [
            'files' => [
                '/index.md' => '# Index',
                '/page2.md' => '# Page 2',
                '/page2.latte' => 'Page 2',
                '/page3.md' => '# Page 3',
            ],
            'limit' => 2,
            'offset' => 0,
            'expected' => 2,
            'validator' => function (Folder $folder, BasicPageSet $pages) {
                //self::assertNotNull($folder);
            },
        ];

        yield 'with offset' => [
            'files' => [
                '/page1.md' => '# Page 1',
                '/page2.md' => '# Page 2',
                '/page2.latte' => 'Page 2',
                '/page3.md' => '# Page 3',
                '/page4.md' => '# Page 4',
                '/page5.md' => '# Page 5',
            ],
            'limit' => 2,
            'offset' => 1,
            'expected' => 2,
            'validator' => function (Folder $folder, BasicPageSet $pages) {
                self::assertInstanceOf(Page::class, $pages->get('page2'));
                self::assertInstanceOf(Page::class, $pages->get('page3'));
            },
        ];
    }

    /**
     * @param array<string, string> $files
     *   A map from file names to file contents. These files will
     *   be created in the VFS to setup the test.
     */
    #[Test, DataProvider('limitProvider')]
    public function limit(array $files, int $limit, int $offset, int $expected, ?\Closure $validator = null): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        foreach ($files as $file => $content) {
            file_put_contents($routesPath . $file, $content);
        }

        $tree = new PageTree($this->cache, $this->parser, $routesPath);

        $folder = $tree->folder('/');

        $result = $folder->limit($limit, $offset);

        self::assertCount($expected, $result);

        if ($validator) {
            $validator($folder, $result);
        }
    }

    public static function paginationProvider(): iterable
    {
        yield 'first page' => [
            'files' => [
                '/page1.md' => '# Page 1',
                '/page2.md' => '# Page 2',
                '/page3.md' => '# Page 3',
                '/page4.md' => '# Page 4',
                '/page5.md' => '# Page 5',
            ],
            'pageSize' => 2,
            'pageNum' => 1,
            'expectedPages' => ['Page 1', 'Page 2'],
        ];

        yield 'middle page' => [
            'files' => [
                '/page1.md' => '# Page 1',
                '/page2.md' => '# Page 2',
                '/page3.md' => '# Page 3',
                '/page4.md' => '# Page 4',
                '/page5.md' => '# Page 5',
            ],
            'pageSize' => 2,
            'pageNum' => 2,
            'expectedPages' => ['Page 3', 'Page 4'],
        ];

        yield 'last page' => [
            'files' => [
                '/page1.md' => '# Page 1',
                '/page2.md' => '# Page 2',
                '/page3.md' => '# Page 3',
                '/page4.md' => '# Page 4',
                '/page5.md' => '# Page 5',
            ],
            'pageSize' => 2,
            'pageNum' => 3,
            'expectedPages' => ['Page 5'],
        ];
    }

    #[Test, DataProvider('paginationProvider')]
    public function paginate(array $files, int $pageSize, int $pageNum, array $expectedPages, ?\Closure $validator = null): void
    {
        $routesPath = $this->vfs->getChild('routes')?->url();

        foreach ($files as $file => $content) {
            file_put_contents($routesPath . $file, $content);
        }

        $tree = new PageTree($this->cache, $this->parser, $routesPath);

        $folder = $tree->folder('/');

        $result = $folder->paginate($pageSize, $pageNum);

        self::assertEquals($pageSize, $result->pageSize);
        self::assertEquals($pageNum, $result->pageNum);
        self::assertEquals(count($folder), $result->total);
        self::assertEquals(ceil(count($folder)/$pageSize), $result->pageCount);

        self::assertPagesMatch($expectedPages, $result->items);

        if ($validator) {
            $validator($folder, $result);
        }
    }

    private static function assertPagesMatch(array $expectedPages, PageSet $result): void
    {
        $foundPages = array_values(array_map(static fn(Page $p) => $p->title, iterator_to_array($result)));
        self::assertEquals($expectedPages, $foundPages);
    }
}
