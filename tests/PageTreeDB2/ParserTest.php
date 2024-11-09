<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\ClassFinder;
use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\PageTree\FileInterpreter\FileInterpreter;
use Crell\MiDy\PageTree\FileInterpreter\LatteFileInterpreter;
use Crell\MiDy\PageTree\FileInterpreter\MarkdownLatteFileInterpreter;
use Crell\MiDy\PageTree\FileInterpreter\MultiplexedFileInterpreter;
use Crell\MiDy\PageTree\FileInterpreter\PhpFileInterpreter;
use Crell\MiDy\PageTree\FileInterpreter\StaticFileInterpreter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * The VFS needs to be static so it's reused, so the require_once() call in the PHP interpreter
     * can not require the "same" file multiple times, leading to double-declaration errors.
     */
    protected static vfsStreamDirectory $vfs;

    #[BeforeClass]
    public static function initFilesystem(): void
    {
        // This mess is because vfsstream doesn't let you create multiple streams
        // at the same time.  Which is dumb.
        $structure = [
            'cache' => [],
            'routes' => [],
        ];

        self::$vfs = vfsStream::setup('root', null, $structure);
    }

    protected function makeFileParser(): FileParser
    {
        $p = new MultiplexedFileParser();
        $p->addParser(new StaticFileParser(new StaticRoutes()));
//        $p->addParser(new PhpFileInterpreter(new ClassFinder()));
//        $p->addParser(new LatteFileInterpreter());
//        $p->addParser(new MarkdownLatteFileInterpreter(new MarkdownPageLoader()));

        return $p;
    }

    #[Test]
    public function stuff(): void
    {
        $db = new \PDO('sqlite::memory:');

        $cache = new PageCacheDB($db);

        $cachePath = self::$vfs->getChild('cache')?->url();
        $routesPath = self::$vfs->getChild('routes')?->url();

        file_put_contents($routesPath . '/foo.html', 'Foo');
        file_put_contents($routesPath . '/bar.html', 'Bar');
        file_put_contents($routesPath . '/folder.midy', '{"order": "Desc"}');
        mkdir($routesPath . '/subdir');

        $parser = new Parser($routesPath, $cache, $this->makeFileParser());

        $pagetree = new PageTree($cache, $parser);

        $pagetree->folder('/');
    }
}
