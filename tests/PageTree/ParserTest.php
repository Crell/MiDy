<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\SetupFilesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    use SetupFilesystem;
    use SetupParser;

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

        $this->parser->parseFolder($this->routesPath . '/subdir', '/subdir', []);

        $records = $this->yiiConn->createCommand("SELECT * FROM page WHERE logicalPath='/subdir/beep'")->queryAll();
        self::assertCount(1, $records);

        $allRecords = $this->yiiConn->createCommand("SELECT * FROM page")->queryAll();
        self::assertCount(2, $allRecords);

        $records = $this->yiiConn->createCommand("SELECT * FROM folder WHERE logicalPath='/subdir'")->queryAll();
        self::assertCount(1, $records);

    }
}
