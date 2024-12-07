<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests that use the VFS need to run in their own separate processes.
 *
 * Otherwise, if a .php file is parsed, it will result in double-declaration
 * of the class, and a fatal error.
 */
#[RunTestsInSeparateProcesses]
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

    #[Test]
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

    #[Test]
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
        $children = $folder->children;

        self::assertCount(2, $children);
        self::assertCount(2, $folder);
    }

    #[Test]
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
}
