<?php

declare(strict_types=1);

namespace Crell\MiDy\MarkdownDeserializer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MarkdownPageTest extends TestCase
{

    public static function pageProvider(): iterable
    {
        yield 'no summary' => [
            'page' => new MarkdownPage(content: ''),
            'summary' => '',
        ];

        yield 'explicit summary' => [
            'page' => new MarkdownPage(content: '', summary: 'Summary here'),
            'summary' => 'Summary here',
        ];

        yield 'explicit summary wins over body' => [
            'page' => new MarkdownPage(
                content: <<<END
                Foo here
                <!--summary-->
                Body summary here
                <!--/summary-->
                END,
                summary: 'Summary here'
            ),
            'summary' => 'Summary here',
        ];

        yield 'no summary extracts from body' => [
            'page' => new MarkdownPage(
                content: <<<END
                Foo here
                <!--summary-->
                Body summary here
                <!--/summary-->
                END,
            ),
            'summary' => 'Body summary here',
        ];
    }

    #[Test, DataProvider('pageProvider')]
    public function correct_summary_extracted(MarkdownPage $page, string $summary): void
    {
        self::assertSame($page->summary(), $summary);
    }
}
