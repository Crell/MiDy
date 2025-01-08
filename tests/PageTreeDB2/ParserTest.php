<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTreeDB2\Parser\FileParser;
use Crell\MiDy\PageTreeDB2\Parser\MultiplexedFileParser;
use Crell\MiDy\PageTreeDB2\Parser\Parser;
use Crell\MiDy\PageTreeDB2\Parser\StaticFileParser;
use Crell\MiDy\SetupFilesystem;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    use SetupFilesystem;
    use SetupCache;

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
        file_put_contents($this->routesPath . '/foo.html', 'Foo');
        file_put_contents($this->routesPath . '/bar.html', 'Bar');
        file_put_contents($this->routesPath . '/folder.midy', '{"order": "Desc"}');
        mkdir($this->routesPath . '/subdir');
        file_put_contents($this->routesPath . '/subdir/baz.html', 'Baz');
        file_put_contents($this->routesPath . '/subdir/beep.html', 'Beep');
        file_put_contents($this->routesPath . '/subdir/folder.midy', '{"order": "Desc"}');

        $parser = new Parser($this->cache, $this->makeFileParser());

        $parser->parseFolder($this->routesPath . '/subdir', '/subdir', []);

        $records = $this->db->query("SELECT * FROM file WHERE logicalPath='/subdir/beep'")->fetchAll(\PDO::FETCH_OBJ);
        self::assertCount(1, $records);

        $allRecords = $this->db->query("SELECT * FROM file")->fetchAll(\PDO::FETCH_OBJ);
        self::assertCount(2, $allRecords);

        $records = $this->db->query("SELECT * FROM folder WHERE logicalPath='/subdir'")->fetchAll(\PDO::FETCH_OBJ);
        self::assertCount(1, $records);

    }
}
