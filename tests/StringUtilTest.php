<?php

declare(strict_types=1);

namespace Crell\MiDy;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StringUtilTest extends TestCase
{
    public static function extractProvider(): iterable
    {
        yield 'empty string' => [
            'str' => '',
            'start' => '<!--',
            'end' => '-->',
            'expected' => null,
        ];

        yield 'matching delim' => [
            'str' => 'foo @ bar @ baz',
            'start' => '@',
            'end' => '@',
            'expected' => ' bar ',
        ];

        yield 'matching delim using the default pattern delimiter' => [
            'str' => 'foo # bar # baz',
            'start' => '#',
            'end' => '#',
            'expected' => ' bar ',
        ];

        yield 'latte frontmatter' => [
            'str' => <<<END
            Foo here
            {*---
            title: foo
            bar: baz
            ---*}
            END,
            'start' => '{*---',
            'end' => '---*}',
            'expected' => <<<END
            title: foo
            bar: baz
            END,
        ];

        yield 'HTML comment tag delimiters' => [
            'str' => <<<END
            Foo here
            <!--summary-->
            Things here
            <!--/summary-->
            END,
            'start' => '<!--summary-->',
            'end' => '<!--/summary-->',
            'expected' => <<<END
            Things here
            END,
        ];
    }

    #[Test, DataProvider('extractProvider')]
    public function extract_finds_right_string(string $str, string $start, string $end, ?string $expected): void
    {
        $result = str_extract_between($str, $start, $end);
        self::assertSame(trim($expected ?? ''), trim($result ?? ''));
    }
}
