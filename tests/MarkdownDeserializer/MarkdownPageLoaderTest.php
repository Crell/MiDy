<?php

declare(strict_types=1);

namespace Crell\MiDy\MarkdownDeserializer;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MarkdownPageLoaderTest extends TestCase
{
    protected vfsStreamDirectory $vfs;

    protected static array $files = [
        'basic.md' => [
            'content' => <<<END
                # Stuff
                Here
            END,
            'expectedTitle' => 'Basic',
            'expectedPath' => '/files/basic',
        ],
        'customized.md' => [
            'content' => <<<END
                ---
                title: Custom title
                slug: custom
                ---
                # Stuff
                Here
            END,
            'expectedTitle' => 'Custom title',
            'expectedPath' => '/files/custom',
        ],
    ];

    public static function fileProvider(): iterable
    {
        yield 'basic with header' => [
            'filename' => 'has_data.md',
            'content' => <<<END
            ---
            title: Custom title
            slug: custom
            ---
            # Stuff
            Here
            END,
            'expected' => New MarkdownPage(
                content: "# Stuff\nHere",
                title: "Custom title",
                slug: "custom",
            ),
        ];

        yield 'basic with no header' => [
            'filename' => 'no_header.md',
            'content' => <<<END
            Stuff Here
            END,
            'expected' => New MarkdownPage(
                content: "Stuff Here",
                title: "",
                slug: null,
            ),
        ];

        yield 'basic with no header but an h1' => [
            'filename' => 'h1.md',
            'content' => <<<END
            # Stuff
            Here
            END,
            'expected' => New MarkdownPage(
                content: "Here",
                title: "Stuff",
                summary: '',
                slug: null,
            ),
        ];

        yield 'header without title and an h1' => [
            'filename' => 'header_h1.md',
            'content' => <<<END
            ---
            slug: test
            ---
            # Stuff
            Here
            END,
            'expected' => New MarkdownPage(
                content: "Here",
                title: "Stuff",
                summary: '',
                slug: 'test',
            ),
        ];
        yield 'header and a --- character inside the body' => [
            'filename' => 'dashes.md',
            'content' => <<<END
            ---
            slug: test
            ---
            # Stuff
            Here
            ---
            Stuff after an hr here.
            END,
            'expected' => New MarkdownPage(
                content: <<<END
                Here
                ---
                Stuff after an hr here.
                END,
                title: "Stuff",
                summary: '',
                slug: 'test',
            ),
        ];
    }

    #[Test, DataProvider('fileProvider')]
    public function files_load_correctly(string $filename, string $content, MarkdownPage $expected): void
    {
        $this->vfs = vfsStream::setup('files', null, []);
        $basePath = $this->vfs->url() . '/';
        $testFile = $basePath . $filename;
        $content = ltrim($content, ' ');
        file_put_contents($testFile, $content);

        $m = new MarkdownPageLoader();

        $result = $m->load($testFile);

        self::assertInstanceOf(MarkdownPage::class, $result);
        self::assertEquals($expected, $result);
    }
}
