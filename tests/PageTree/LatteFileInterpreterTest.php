<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class LatteFileInterpreterTest extends FileInterpreterTestBase
{
    protected static array $files = [
        'file.latte' => [
            'content' => 'abc123',
            'expectedTitle' => 'File',
            'expectedPath' => '/files/file',
        ],
    ];

    protected function getInterpreter(): FileInterpreter
    {
        return new LatteFileInterpreter();
    }

    public static function frontmatterProvider(): iterable
    {
        yield 'just frontmatter' => [
            'content' => <<<END
            {*---
            title: Title here
            ---*}
            END,
            'expected' => new MiDyFrontMatter(title: 'Title here'),
        ];
        yield 'frontmatter, but not at the start of the file.' => [
            'content' => <<<END
            Stuff here.
            {*---
            title: Title here
            ---*}
            END,
            'expected' => new MiDyFrontMatter(title: 'Title here'),
        ];
        yield 'frontmatter, with stuff before and after.' => [
            'content' => <<<END
            Stuff here.
            {*---
            title: Title here
            ---*}
            More templates here.
            END,
            'expected' => new MiDyFrontMatter(title: 'Title here'),
        ];
    }

    #[Test, DataProvider('frontmatterProvider')]
    public function frontmatter_parses_correctly(string $content, MiDyFrontMatter $expected): void
    {
        $i = $this->getInterpreter();

        $filename = $this->vfs->url() . '/test.latte';

        file_put_contents($filename, $content);

        $result = $i->map(new \SplFileInfo($filename), '/', 'test');

        self::assertInstanceOf(RouteFile::class, $result);
        self::assertEquals($expected, $result->frontmatter);
    }
}
