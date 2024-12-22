<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTreeDB2\Parser\FileParser;
use Crell\MiDy\PageTreeDB2\Parser\MultiplexedFileParser;
use Crell\MiDy\PageTreeDB2\Parser\Parser;
use Crell\MiDy\PageTreeDB2\Parser\StaticFileParser;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    use SetupCache;

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
    public function parser_on_subdir_finds_right_files(): void
    {
        $routesPath = self::$vfs->getChild('routes')?->url();

        file_put_contents($routesPath . '/foo.html', 'Foo');
        file_put_contents($routesPath . '/bar.html', 'Bar');
        file_put_contents($routesPath . '/folder.midy', '{"order": "Desc"}');
        mkdir($routesPath . '/subdir');
        file_put_contents($routesPath . '/subdir/baz.html', 'Baz');
        file_put_contents($routesPath . '/subdir/beep.html', 'Beep');
        file_put_contents($routesPath . '/subdir/folder.midy', '{"order": "Desc"}');

        $parser = new Parser($this->cache, $this->makeFileParser());

        $parser->parseFolder($routesPath . '/subdir', '/subdir', []);

        $records = $this->db->query("SELECT * FROM file WHERE logicalPath='/subdir/beep'")->fetchAll(\PDO::FETCH_OBJ);
        self::assertCount(1, $records);

        $allRecords = $this->db->query("SELECT * FROM file")->fetchAll(\PDO::FETCH_OBJ);
        self::assertCount(2, $allRecords);

        $records = $this->db->query("SELECT * FROM folder WHERE logicalPath='/subdir'")->fetchAll(\PDO::FETCH_OBJ);
        self::assertCount(1, $records);

    }
}
