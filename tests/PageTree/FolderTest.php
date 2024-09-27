<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\PageTree\FolderParser\FolderParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FolderTest extends TestCase
{
    public static function limitProvider(): iterable
    {
        yield '3 pages, show 2' => [
            'data' => new FolderData([
                'page1' => self::makePage('/page1', '/page1', ['md']),
                'page2' => self::makePage('/page2', '/page2', ['md']),
                'page3' => self::makePage('/page3', '/page3', ['md']),
            ]),
            'limit' => 2,
            'expected' => 2,
        ];

        yield '3 pages, show 4' => [
            'data' => new FolderData([
                'page1' => self::makePage('/page1', '/page1', ['latte']),
                'page2' => self::makePage('/page2', '/page2', ['latte']),
                'page3' => self::makePage('/page3', '/page3', ['latte']),
            ]),
            'limit' => 4,
            'expected' => 3,
        ];

        yield 'with extra route files, limited' => [
            'data' => new FolderData([
                'page1' => self::makePage('/page1', '/page1', ['latte']),
                'page2' => self::makePage('/page2', '/page2', ['latte', 'md']),
                'page3' => self::makePage('/page3', '/page3', ['latte']),
            ]),
            'limit' => 2,
            'expected' => 2,
        ];

        yield 'with extra route files, not limited' => [
            'data' => new FolderData([
                'page1' => self::makePage('/page1', '/page1', ['latte']),
                'page2' => self::makePage('/page2', '/page2', ['latte', 'md']),
                'page3' => self::makePage('/page3', '/page3', ['latte']),
            ]),
            'limit' => 4,
            'expected' => 3,
        ];

        yield 'with an index file, first' => [
            'data' => new FolderData([
                'index' => self::makePage('/index', '/index', ['md']),
                'page2' => self::makePage('/page2', '/page2', ['latte', 'md']),
                'page3' => self::makePage('/page3', '/page3', ['latte']),
            ]),
            'limit' => 2,
            'expected' => 2,
            'validator' => function (FolderData $data, FolderData $folder) {
                self::assertNotNull($folder->indexPage);
            },
        ];
    }

    #[Test, DataProvider('limitProvider')]
    public function limit(FolderData $data, int $limit, int $expected, ?\Closure $validator = null): void
    {
        $folder = new Folder('/', '/', $this->fakeParser($data));

        $result = $folder->limit($limit);

        self::assertCount($expected, $result);

        if ($validator) {
            $validator($data, $result);
        }
    }

    public static function paginationProvider(): iterable
    {
        yield 'first page' => [
            'data' => new FolderData([
                'page1' => self::makePage('/page1', '/page1', ['latte']),
                'page2' => self::makePage('/page2', '/page2', ['latte']),
                'page3' => self::makePage('/page3', '/page3', ['latte']),
                'page4' => self::makePage('/page4', '/page4', ['latte']),
                'page5' => self::makePage('/page5', '/page5', ['latte']),
            ]),
            'pageSize' => 2,
            'pageNum' => 1,
            'expectedPages' => ['Page1', 'Page2'],
        ];

        yield 'middle page' => [
            'data' => new FolderData([
                'page1' => self::makePage('/page1', '/page1', ['latte']),
                'page2' => self::makePage('/page2', '/page2', ['latte']),
                'page3' => self::makePage('/page3', '/page3', ['latte']),
                'page4' => self::makePage('/page4', '/page4', ['latte']),
                'page5' => self::makePage('/page5', '/page5', ['latte']),
            ]),
            'pageSize' => 2,
            'pageNum' => 2,
            'expectedPages' => ['Page3', 'Page4'],
        ];

        yield 'last page' => [
            'data' => new FolderData([
                'page1' => self::makePage('/page1', '/page1', ['latte']),
                'page2' => self::makePage('/page2', '/page2', ['latte']),
                'page3' => self::makePage('/page3', '/page3', ['latte']),
                'page4' => self::makePage('/page4', '/page4', ['latte']),
                'page5' => self::makePage('/page5', '/page5', ['latte']),
            ]),
            'pageSize' => 2,
            'pageNum' => 3,
            'expectedPages' => ['Page5'],
        ];
    }

    #[Test, DataProvider('paginationProvider')]
    public function paginate(FolderData $data, int $pageSize, int $pageNum, array $expectedPages, ?\Closure $validator = null): void
    {
        $folder = new Folder('/', '/', $this->fakeParser($data));

        $result = $folder->paginate($pageSize, $pageNum);

        self::assertEquals($pageSize, $result->pageSize);
        self::assertEquals($pageNum, $result->pageNum);
        self::assertEquals(count($folder), $result->total);
        self::assertEquals(ceil(count($folder)/$pageSize), $result->pageCount);

        self::assertPagesMatch($expectedPages, $result->items);

        if ($validator) {
            $validator($data, $result);
        }
    }

    public static function filterProvider(): iterable
    {
        yield 'just markdown' => [
            'data' => new FolderData([
                'page1' => self::makePage('/page1', '/page1', ['md']),
                'page2' => self::makePage('/page2', '/page2', ['latte']),
                'page3' => self::makePage('/page3', '/page3', ['md']),
            ]),
            'filter' => fn(Page $p) => array_key_exists('md', $p->variants()),
            'expectedPages' => ['Page1', 'Page3'],
        ];

        yield 'exclude hidden' => [
            'data' => new FolderData([
                'page1' => self::makePage('/page1', '/page1', ['md']),
                'page2' => self::makePage('/page2', '/page2', ['md'], new BasicPageInformation(hidden: true)),
                'page3' => self::makePage('/page3', '/page3', ['md']),
            ]),
            'filter' => fn(Page $p) => !$p->hidden(),
            'expectedPages' => ['Page1', 'Page3'],
        ];
    }

    #[Test, DataProvider('filterProvider')]
    public function filter(FolderData $data, \Closure $filter, array $expectedPages, ?\Closure $validator = null): void
    {
        $folder = new Folder('/', '/', $this->fakeParser($data));

        $result = $folder->filter($filter);

        self::assertPagesMatch($expectedPages, $result);

        if ($validator) {
            $validator($data, $result);
        }
    }

    private static function assertPagesMatch(array $expectedPages, PageSet $result): void
    {
        $foundPages = array_values(array_map(static fn(Page $p) => $p->title(), iterator_to_array($result)));
        self::assertEquals($expectedPages, $foundPages);
    }

    private function fakeParser(FolderData $data): FolderParser
    {
        return new readonly class($data) implements FolderParser {
            public function __construct(public FolderData $data) {}

            public function loadFolder(Folder $folder): FolderData
            {
                return $this->data;
            }
        };
    }

    private static function makePage(string $physicalPath, string $logicalPath, array $variants, BasicPageInformation $frontMatter = new BasicPageInformation()): Page
    {
        $files = [];
        foreach ($variants as $ext) {
            $files[$ext] = new PageFile(
                physicalPath: "$physicalPath.$ext",
                logicalPath: "$logicalPath.$ext",
                ext: $ext,
                mtime: time(),
                info: $frontMatter,
            );
        }

        return new AggregatePage($logicalPath, $files);
    }
}
