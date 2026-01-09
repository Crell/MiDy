<?php

namespace Crell\MiDy\PageTree;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BasicPageSetTest extends TestCase
{
    use MakerUtils;

    public static function pageSetExamples(): Generator
    {
        $generator = static function () {
            yield self::makePageRecord('/foo/a', title: 'A');
            yield self::makePageRecord('/foo/b');
            yield self::makePageRecord('/foo/c', routable: false);
            yield self::makePageRecord('/foo/d', tags: ['one']);
            yield self::makePageRecord('/foo/e', tags: ['one']);
        };

        yield 'array based' => [
            'pages' => iterator_to_array($generator()),
        ];

        yield 'generator based' => [
            'pages' => $generator(),
        ];
    }

    /**
     * @param iterable<Page> $pages
     */
    #[Test]
    #[DataProvider('pageSetExamples')]
    public function count_gives_right_number(iterable $pages): void
    {
        $set = new BasicPageSet($pages);

        self::assertCount(5, $set);
    }

    /**
     * @param iterable<Page> $pages
     */
    #[Test]
    #[DataProvider('pageSetExamples')]
    public function iteration_finds_all_values(iterable $pages): void
    {
        $set = new BasicPageSet($pages);

        $count = 0;
        foreach ($set as $page) {
            $count++;
        }

        self::assertEquals(5, $count);
    }

    /**
     * @param iterable<Page> $pages
     */
    #[Test]
    #[DataProvider('pageSetExamples')]
    public function all_finds_all_values(iterable $pages): void
    {
        $set = new BasicPageSet($pages)->all();

        $count = 0;
        foreach ($set as $page) {
            $count++;
        }

        self::assertEquals(5, $count);
    }

    /**
     * @param iterable<Page> $pages
     */
    #[Test]
    #[DataProvider('pageSetExamples')]
    public function limit_works(iterable $pages): void
    {
        $set = new BasicPageSet($pages)->limit(2);

        self::assertCount(2, $set);
    }

    /**
     * @param iterable<Page> $pages
     */
    #[Test]
    #[DataProvider('pageSetExamples')]
    public function filter_works(iterable $pages): void
    {
        $pagination = new BasicPageSet($pages)->filter(fn(Page $p): bool => $p->routable === true, 3);

        self::assertEquals(4, $pagination->total);
        self::assertCount(3, $pagination->items);
        self::assertEquals(2, $pagination->lastPageNum);
    }

    /**
     * @param iterable<Page> $pages
     */
    #[Test]
    #[DataProvider('pageSetExamples')]
    public function filteranyTag_works(iterable $pages): void
    {
        $pagination = new BasicPageSet($pages)->filterAnyTag(['one'], 3);

        self::assertEquals(2, $pagination->total);
    }

    /**
     * @param iterable<Page> $pages
     */
    #[Test]
    #[DataProvider('pageSetExamples')]
    public function get_works(iterable $pages): void
    {
        $page = new BasicPageSet($pages)->get('A');

        self::assertInstanceOf(PageRecord::class, $page);
        self::assertEquals('A', $page->title);
    }
}
