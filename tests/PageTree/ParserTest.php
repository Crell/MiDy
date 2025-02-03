<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\SetupFilesystem;
use PHPUnit\Framework\Attributes\DataProvider;
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

        $records = $this->conn->createCommand("SELECT * FROM page WHERE logicalPath='/subdir/beep'")->queryAll();
        self::assertCount(1, $records);

        $allRecords = $this->conn->createCommand("SELECT * FROM page")->queryAll();
        self::assertCount(2, $allRecords);

        $records = $this->conn->createCommand("SELECT * FROM folder WHERE logicalPath='/subdir'")->queryAll();
        self::assertCount(1, $records);
    }

    public static function parser_data_examples(): iterable
    {
        $halloween = new \DateTimeImmutable('2024-10-31');
        $halloweenStamp = $halloween->getTimestamp();

        yield 'Basic static file' => [
            'file' => '/foo.css',
            'content' => 'ABC 123',
            'expected' => [
                'logicalPath' => '/foo',
                'ext' => 'css',
                'hidden' => true,
            ],
        ];

        yield 'Latte template header is parsed' => [
            'file' => '/foo.latte',
            'content' => <<<END
            {*---
            title: Title here
            ---*}
            Template bits here.
            END,
            'expected' => [
                'logicalPath' => '/foo',
                'ext' => 'latte',
                'hidden' => false,
                'title' => 'Title here',
            ],
        ];

        yield 'PHP attribute is parsed' => [
            'file' => '/foo.php',
            'content' => <<<END
            <?php

            use Crell\MiDy\PageTree\Attributes\PageRoute;

            #[PageRoute(title: 'Title here')]
            class Dummy {
                public function get() {}
            }
            END,
            'expected' => [
                'logicalPath' => '/foo',
                'ext' => 'php',
                'hidden' => false,
                'title' => 'Title here',
            ],
        ];

        yield 'Markdown h1 is parsed as title' => [
            'file' => '/foo.md',
            'content' => <<<END
            # Title here
            END,
            'expected' => [
                'logicalPath' => '/foo',
                'ext' => 'md',
                'hidden' => false,
                'title' => 'Title here',
            ],
        ];

        yield 'Markdown header title is parsed' => [
            'file' => '/foo.md',
            'content' => <<<END
            ---
            title: A title
            ---
            Body here
            END,
            'expected' => [
                'logicalPath' => '/foo',
                'ext' => 'md',
                'hidden' => false,
                'title' => 'A title',
            ],
        ];

        yield 'File mtime is used as published and last-modified dates' => [
            'file' => '/foo.md',
            'content' => <<<END
            # A title
            END,
            'mtime' => $halloweenStamp,
            'expected' => [
                'logicalPath' => '/foo',
                'ext' => 'md',
                'hidden' => false,
                'mtime' => $halloweenStamp,
                'publishDate' => $halloween,
                'lastModifiedDate' => $halloween,
            ],
        ];
    }

    #[Test, DataProvider('parser_data_examples')]
    public function parser_data(string $file, string $content, array $expected, ?int $mtime = null): void
    {
        $filename = $this->routesPath . $file;

        file_put_contents($filename, $content);
        if ($mtime) {
            touch($filename, $mtime);
            clearstatcache(true, $filename);
        }

        $parsedFile = $this->parser->parseFile(new \SplFileInfo($filename), '/');

        // We only want to check selected fields in each case, so rather than build
        // a complete expected ParsedFile object with pointless data, we just test
        // selected fields directly.
        self::assertEquals($filename, $parsedFile->physicalPath);
        foreach ($expected as $field => $value) {
            self::assertEquals($value, $parsedFile->$field);
        }
    }
}
