<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\Config\StaticRoutes;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PageTreeTest extends TestCase
{
    use SetupCache;

    /**
     * The VFS needs to be static so it's reused, so the require_once() call in the PHP interpreter
     * can not require the "same" file multiple times, leading to double-declaration errors.
     */
    protected static vfsStreamDirectory $vfs;

    private Parser $parser;

    #[Before]
    public function setupParser(): void
    {
        $fileParser = new MultiplexedFileParser();
        $fileParser->addParser(new StaticFileParser(new StaticRoutes()));

        $this->parser = new Parser($this->setupCache(), $fileParser);
    }

    #[BeforeClass]
    public static function initFilesystem(): vfsStreamDirectory
    {
        // This mess is because vfsstream doesn't let you create multiple streams
        // at the same time.  Which is dumb.
        $structure = [
            'cache' => [],
            'routes' => [],
        ];

        return self::$vfs = vfsStream::setup('root', null, $structure);
    }

    #[Test]
    public function stuff(): void
    {
        $routesPath = self::$vfs->getChild('routes')?->url();

        file_put_contents($routesPath . '/foo.html', 'Foo');
        file_put_contents($routesPath . '/bar.html', 'Bar');
        file_put_contents($routesPath . '/folder.midy', '{"order": "Desc"}');
        mkdir($routesPath . '/subdir');

        $tree = new PageTree($this->cache, $this->parser, $routesPath);

        $folder = $tree->folder('/subdir');

        self::assertEquals('/subdir', $folder->logicalPath);

    }
}
