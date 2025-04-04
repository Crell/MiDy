<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\SetupFilesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class YiiDbPageCacheTest extends TestCase
{
    use SetupFilesystem;
    use SetupDB;
    use MakerUtils;

    #[Test, DoesNotPerformAssertions]
    public function reinitialize_creates_tables_if_they_dont_exist(): void
    {
        $cache = new YiiDbPageCache($this->conn);

        $cache->reinitialize();

        // These will throw an exception if the tables do not exist.
        $this->conn->createCommand("SELECT 1 FROM folder")->queryOne();
        $this->conn->createCommand("SELECT 1 FROM page")->queryOne();
    }

    #[Test, DoesNotPerformAssertions]
    public function reinitialize_recreates_tables_if_they_do_exist(): void
    {
        $cache = new YiiDbPageCache($this->conn);

        $this->conn->createCommand('CREATE TABLE IF NOT EXISTS folder(fake int)')->execute();

        $cache->reinitialize();

        // These will throw an exception if the tables do not exist.
        $this->conn->createCommand("SELECT 1 FROM folder")->execute();
        $this->conn->createCommand("SELECT 1 FROM page")->execute();
    }

    #[Test]
    public function can_write_new_folder(): void
    {
        $cache = new YiiDbPageCache($this->conn);

        $cache->reinitialize();

        $folder = self::makeParsedFolder(physicalPath: '/foo');

        $cache->writeFolder($folder);

        $record = $this->conn->createCommand("SELECT * FROM folder WHERE logicalPath='/foo'")->queryOne();
        self::assertEquals((string)$folder->physicalPath, $record['physicalPath']);
    }

    #[Test]
    public function can_write_updated_folder(): void
    {
        $cache = new YiiDbPageCache($this->conn);

        $cache->reinitialize();

        $folder = self::makeParsedFolder(physicalPath: '/foo');
        $cache->writeFolder($folder);

        $newFolder = new ParsedFolder(LogicalPath::create('/foo'), '/foo', 123456, true, 'Foo2');
        $cache->writeFolder($newFolder);

        $record = $this->conn->createCommand("SELECT * FROM folder WHERE logicalPath='/foo'")->queryOne();
        self::assertEquals($newFolder->physicalPath, $record['physicalPath']);
        self::assertEquals($newFolder->mtime, $record['mtime']);
        self::assertEquals($newFolder->flatten, $record['flatten']);
        self::assertEquals($newFolder->title, $record['title']);
    }

    #[Test]
    public function can_read_folder(): void
    {
        $cache = new YiiDbPageCache($this->conn);

        $cache->reinitialize();

        $folder = self::makeParsedFolder(physicalPath: '/foo');
        $cache->writeFolder($folder);

        $savedFolder = $cache->readFolder(LogicalPath::create('/foo'));

        self::assertEquals((string)$folder->physicalPath, (string)$savedFolder->physicalPath);
        self::assertEquals($folder->mtime, $savedFolder->mtime);
        self::assertEquals($folder->flatten, $savedFolder->flatten);
        self::assertEquals($folder->title, $savedFolder->title);
    }

    #[Test]
    public function returns_null_for_missing_folder(): void
    {
        $cache = new YiiDbPageCache($this->conn);

        $cache->reinitialize();

        $savedFolder = $cache->readFolder(LogicalPath::create('/foo'));

        self::assertNull($savedFolder);
    }

    #[Test]
    public function can_delete_folder(): void
    {
        $cache = new YiiDbPageCache($this->conn);
        $cache->reinitialize();

        $folder = self::makeParsedFolder(physicalPath: '/foo');
        $cache->writeFolder($folder);

        $cache->deleteFolder(LogicalPath::create('/foo'));

        $record = $this->conn->createCommand("SELECT * FROM folder WHERE logicalPath='/foo'")->queryOne();

        self::assertNull($record);
    }

    public static function page_data(): iterable
    {
        yield 'single file page' => [
            'folder' => self::makeParsedFolder(physicalPath: '/foo'),
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/test.md'),
            ],
            'pagePath' => '/foo/test',
            'dbValidation' => function (self $test) {
                $page = $test->getPage('/foo/test');
                self::assertFalse((bool)$page['hidden']);
            },
            'validation' => function (PageRecord $page) {
                self::assertFalse($page->hidden);
                self::assertFalse($page->isFolder);
            },
        ];

        yield 'multi-file page' => [
            'folder' => self::makeParsedFolder(physicalPath: '/foo'),
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/test.md'),
                self::makeParsedFile(physicalPath: '/foo/test.latte'),
            ],
            'pagePath' => '/foo/test',
            'dbValidation' => function (self $test) {
                $page = $test->getPage('/foo/test');
                self::assertFalse((bool)$page['hidden']);
            },
            'validation' => function (PageRecord $page) {
                self::assertFalse($page->hidden);
                self::assertFalse($page->isFolder);
            },
        ];

        yield 'multi-file page, some hidden' => [
            'folder' => self::makeParsedFolder(physicalPath: '/foo'),
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/test.md'),
                self::makeParsedFile(physicalPath: '/foo/test.latte'),
                self::makeParsedFile(physicalPath: '/foo/test.gif', hidden: true),
            ],
            'pagePath' => '/foo/test',
            'dbValidation' => function (self $test) {
//                $page = $test->getPage('/foo/test');
//                self::assertFalse((bool)$page['hidden']);
            },
            'validation' => function (PageRecord $page) {
                self::assertFalse($page->hidden);
                self::assertFalse($page->isFolder);
            },
        ];

        yield 'multi-file page, all hidden' => [
            'folder' => self::makeParsedFolder(physicalPath: '/foo'),
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/test.md', hidden: true),
                self::makeParsedFile(physicalPath: '/foo/test.latte', hidden: true),
                self::makeParsedFile(physicalPath: '/foo/test.gif', hidden: true),
            ],
            'pagePath' => '/foo/test',
            'dbValidation' => function (self $test) {
//                $page = $test->getPage('/foo/test');
//                self::assertTrue((bool)$page['hidden']);
            },
            'validation' => function (PageRecord $page) {
                self::assertTrue($page->hidden);
                self::assertFalse($page->isFolder);
            },
        ];

        yield 'multi-file page, tagged' => [
            'folder' => self::makeParsedFolder(physicalPath: '/foo'),
            'files' => [
                self::makeParsedFile(physicalPath: '/foo/test.md', tags: ['a', 'b']),
                self::makeParsedFile(physicalPath: '/foo/test.latte', tags: ['b', 'c']),
                self::makeParsedFile(physicalPath: '/foo/test.gif', hidden: true),
            ],
            'pagePath' => '/foo/test',
            'dbValidation' => function (self $test) {
                $page = $test->getPage('/foo/test');
                $tags = json_decode($page['tags'], true, 512, JSON_THROW_ON_ERROR);
                self::assertEquals(['a', 'b', 'c'], $tags);
            },
            'validation' => function (PageRecord $page) {
                self::assertFalse($page->hidden);
                self::assertFalse($page->isFolder);
                self::assertEquals(['a', 'b', 'c'], $page->tags);
            },
        ];
    }

    /**
     * @param array<ParsedFile> $files
     */
    #[Test, DataProvider('page_data')]
    public function page_save_and_load(ParsedFolder $folder, array $files, string $pagePath, \Closure $dbValidation, \Closure $validation): void
    {
        $cache = new YiiDbPageCache($this->conn);
        $cache->reinitialize();

        $cache->writeFolder($folder);

        $pageWrite = new PageData($pagePath, $files);
        $cache->writePage($pageWrite);

        $dbValidation($this);

        $pageRead = $cache->readPage(LogicalPath::create($pagePath));

        self::assertEquals('/foo/test', $pageRead->path);
        self::assertEquals('/foo', $pageRead->folder);
        self::assertCount(count($files), $pageRead->files);

        $validation($pageRead);
    }

    #[Test]
    public function page_load_missing_page_returns_null(): void
    {
        $cache = new YiiDbPageCache($this->conn);
        $cache->reinitialize();

        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/foo'));

        $record = $cache->readPage(LogicalPath::create('/foo/bar'));

        self::assertNull($record);
    }

    public static function query_pages_data(): iterable
    {
        yield 'search by folder, shallow' => [
            'folders' => [
                self::makeParsedFolder(physicalPath: '/foo'),
                self::makeParsedFolder(physicalPath: '/foo/sub'),
                self::makeParsedFolder(physicalPath: '/bar'),
            ],
            'pages' => [
                new PageData('/foo/a', [
                    self::makeParsedFile(physicalPath: '/foo/a.md'),
                    self::makeParsedFile(physicalPath: '/foo/a.txt'),
                ]),
                new PageData('/foo/b', [
                    self::makeParsedFile(physicalPath: '/foo/b.md'),
                ]),
                new PageData('/bar/c', [
                    self::makeParsedFile(physicalPath: '/bar/c.md'),
                ]),
            ],
            'query' => [
                'folder' => '/foo'
            ],
            'expectedCount' => 2,
            'totalPages' => 2,
            'validator' => function (QueryResult $queryResult) {
                $paths = array_column($queryResult->pages, 'path');
                self::assertContains('/foo/a', $paths);
                self::assertContains('/foo/b', $paths);
            },
        ];

        yield 'search by folder, deep' => [
            'folders' => [
                self::makeParsedFolder(physicalPath: '/foo'),
                self::makeParsedFolder(physicalPath: '/foobar'),
                self::makeParsedFolder(physicalPath: '/foo/sub'),
                self::makeParsedFolder(physicalPath: '/bar'),
                self::makeParsedFolder(physicalPath: '/bar/foo'),
            ],
            'pages' => [
                new PageData('/foo/a', [
                    self::makeParsedFile(physicalPath: '/foo/a.md'),
                    self::makeParsedFile(physicalPath: '/foo/a.txt'),
                ]),
                new PageData('/foo/b', [
                    self::makeParsedFile(physicalPath: '/foo/b.md'),
                ]),
                new PageData('/bar/c',  [
                    self::makeParsedFile(physicalPath: '/bar/c.md'),
                ]),
                new PageData('/foo/sub/y', [
                    self::makeParsedFile(physicalPath: '/foo/sub/y.md'),
                ]),
                new PageData('/foobar', [
                    self::makeParsedFile(physicalPath: '/foobar/fake.md'),
                ]),
                new PageData('/foobar/fake', [
                    self::makeParsedFile(physicalPath: '/foobar/fake.md'),
                ]),
                new PageData('/bar/foo/nope', [
                    self::makeParsedFile(physicalPath: '/bar/foo/nope.md'),
                ]),
            ],
            'query' => [
                'folder' => '/foo',
                'deep' => true,
            ],
            'expectedCount' => 3,
            'totalPages' => 3,
            'validator' => function (QueryResult $queryResult) {
                $paths = array_column($queryResult->pages, 'path');
                self::assertContains('/foo/a', $paths);
                self::assertContains('/foo/b', $paths);
                self::assertContains('/foo/sub/y', $paths);
            },
        ];

        $hiddenPages = [
            new PageData('/foo/a', [
                self::makeParsedFile(physicalPath: '/foo/a.md', hidden: true),
                self::makeParsedFile(physicalPath: '/foo/a.txt', hidden: true),
            ]),
            new PageData('/foo/b', [
                self::makeParsedFile(physicalPath: '/foo/b.md'),
                self::makeParsedFile(physicalPath: '/foo/b.txt', hidden: true),
            ]),
            new PageData('/bar/c', [
                self::makeParsedFile(physicalPath: '/bar/c.md', hidden: true),
            ]),
            new PageData('/foo/sub/y', [
                self::makeParsedFile(physicalPath: '/foo/sub/y.md'),
            ]),
        ];

        yield 'exclude hidden by default' => [
            'folders' => [
                self::makeParsedFolder(physicalPath: '/foo'),
                self::makeParsedFolder(physicalPath: '/foo/sub'),
                self::makeParsedFolder(physicalPath: '/bar'),
            ],
            'pages' => $hiddenPages,
            'query' => [
                'includeHidden' => false,
            ],
            'expectedCount' => 2,
            'totalPages' => 2,
            'validator' => function (QueryResult $queryResult) {
                $paths = array_column($queryResult->pages, 'path');
                self::assertNotContains('/foo/a', $paths);
                self::assertContains('/foo/b', $paths);
                self::assertNotContains('/foo/c', $paths);
                self::assertContains('/foo/sub/y', $paths);
            },
        ];

        yield 'include hidden' => [
            'folders' => [
                self::makeParsedFolder(physicalPath: '/foo'),
                self::makeParsedFolder(physicalPath: '/foo/sub'),
                self::makeParsedFolder(physicalPath: '/bar'),
            ],
            'pages' => $hiddenPages,
            'query' => [
                'includeHidden' => true,
            ],
            'expectedCount' => 4,
            'totalPages' => 4,
            'validator' => function (QueryResult $queryResult) {
                $paths = array_column($queryResult->pages, 'path');
                self::assertContains('/foo/a', $paths);
                self::assertContains('/foo/b', $paths);
                self::assertContains('/bar/c', $paths);
                self::assertContains('/foo/sub/y', $paths);
            },
        ];

        $routablePages = [
            new PageData('/foo/a', [
                self::makeParsedFile(physicalPath: '/foo/a.md', routable: false),
                self::makeParsedFile(physicalPath: '/foo/a.txt', routable: false),
            ]),
            new PageData('/foo/b', [
                self::makeParsedFile(physicalPath: '/foo/b.md'),
                self::makeParsedFile(physicalPath: '/foo/b.txt', routable: false),
            ]),
            new PageData('/bar/c', [
                self::makeParsedFile(physicalPath: '/bar/c.md', routable: false),
            ]),
            new PageData('/foo/sub/y', [
                self::makeParsedFile(physicalPath: '/foo/sub/y.md'),
            ]),
        ];

        yield 'exclude non-routable by default' => [
            'folders' => [
                self::makeParsedFolder(physicalPath: '/foo'),
                self::makeParsedFolder(physicalPath: '/foo/sub'),
                self::makeParsedFolder(physicalPath: '/bar'),
            ],
            'pages' => $routablePages,
            'query' => [
                'routableOnly' => true,
            ],
            'expectedCount' => 2,
            'totalPages' => 2,
            'validator' => function (QueryResult $queryResult) {
                $paths = array_column($queryResult->pages, 'path');
                self::assertNotContains('/foo/a', $paths);
                self::assertContains('/foo/b', $paths);
                self::assertNotContains('/foo/c', $paths);
                self::assertContains('/foo/sub/y', $paths);
            },
        ];

        yield 'include non-routable' => [
            'folders' => [
                self::makeParsedFolder(physicalPath: '/foo'),
                self::makeParsedFolder(physicalPath: '/foo/sub'),
                self::makeParsedFolder(physicalPath: '/bar'),
            ],
            'pages' => $routablePages,
            'query' => [
                'routableOnly' => false,
            ],
            'expectedCount' => 4,
            'totalPages' => 4,
            'validator' => function (QueryResult $queryResult) {
                $paths = array_column($queryResult->pages, 'path');
                self::assertContains('/foo/a', $paths);
                self::assertContains('/foo/b', $paths);
                self::assertContains('/bar/c', $paths);
                self::assertContains('/foo/sub/y', $paths);
            },
        ];
    }

    public static function query_pages_data_tags_any(): iterable
    {
        $taggedCases = [
            'just A' => [
                'tagsToFind' => ['A'],
                'expectedCount' => 2,
            ],
            'A or B' => [
                'tagsToFind' => ['A', 'B'],
                'expectedCount' => 3,
            ],
            'Just D' => [
                'tagsToFind' => ['D'],
                'expectedCount' => 1,
            ],
        ];
        foreach ($taggedCases as $name => $settings) {
            yield "any-tag search for $name" => [
                'folders' => [
                    self::makeParsedFolder(physicalPath: '/foo'),
                    self::makeParsedFolder(physicalPath: '/foo/sub'),
                    self::makeParsedFolder(physicalPath: '/bar'),
                ],
                'pages' => [
                    new PageData('/foo/a', [
                        self::makeParsedFile(physicalPath: '/foo/a.md', tags: ['A', 'B']),
                        self::makeParsedFile(physicalPath: '/foo/a.txt', tags: ['B', 'C']),
                    ]),
                    new PageData('/foo/b', [
                        self::makeParsedFile(physicalPath: '/foo/b.md', tags: ['D']),
                    ]),
                    new PageData('/foo/c', [
                        self::makeParsedFile(physicalPath: '/foo/c.md'),
                    ]),
                    new PageData('/foo/d', [
                        self::makeParsedFile(physicalPath: '/foo/d.md', tags: ['A', 'C']),
                    ]),
                    new PageData('/foo/e', [
                        self::makeParsedFile(physicalPath: '/foo/e.md'),
                    ]),
                    new PageData('/bar/x', [
                        self::makeParsedFile(physicalPath: '/bar/x.md', tags: ['B']),
                    ]),
                    new PageData('/foo/sub/y', [
                        self::makeParsedFile(physicalPath: '/foo/sub/y.md'),
                    ]),
                ],
                'query' => [
                    'anyTag' => $settings['tagsToFind'],
                ],
                'expectedCount' => $settings['expectedCount'],
                'totalPages' => $settings['expectedCount'],
            ];
        }
    }

    public static function query_pages_data_publication_date(): iterable
    {
        $publishedCases = [
            'should find none' => [
                'query' => ['publishedBefore' => new \DateTimeImmutable('2024-01-15')],
                'expectedCount' => 0,
            ],
            'should find one' => [
                'query' => ['publishedBefore' => new \DateTimeImmutable('2024-02-15')],
                'expectedCount' => 1,
            ],
            'should find lots' => [
                'query' => ['publishedBefore' => new \DateTimeImmutable('2024-05-15')],
                'expectedCount' => 4,
            ],
            'should find on same date' => [
                'query' => ['publishedBefore' => new \DateTimeImmutable('2024-03-01')],
                'expectedCount' => 2,
            ],
            'do not filter by publication date' => [
                'query' => ['publishedBefore' => null],
                'expectedCount' => 7,
            ],
        ];
        foreach ($publishedCases as $name => $settings) {
            yield "publication date search for $name" => [
                'folders' => [
                    self::makeParsedFolder(physicalPath: '/foo'),
                    self::makeParsedFolder(physicalPath: '/foo/sub'),
                    self::makeParsedFolder(physicalPath: '/bar'),
                ],
                'pages' => [
                    new PageData('/foo/a', [
                        self::makeParsedFile(physicalPath: '/foo/a.md', publishDate: new \DateTimeImmutable('2024-01-01')),
                        self::makeParsedFile(physicalPath: '/foo/a.txt', publishDate: new \DateTimeImmutable('2024-02-01')),
                    ]),
                    new PageData('/foo/b', [
                        self::makeParsedFile(physicalPath: '/foo/b.md'),
                    ]),
                    new PageData('/foo/c', [
                        self::makeParsedFile(physicalPath: '/foo/c.md', publishDate: new \DateTimeImmutable('2024-03-01')),
                    ]),
                    new PageData('/foo/d', [
                        self::makeParsedFile(physicalPath: '/foo/d.md', publishDate: new \DateTimeImmutable('2024-04-01')),
                    ]),
                    new PageData('/foo/e', [
                        self::makeParsedFile(physicalPath: '/foo/e.md', publishDate: new \DateTimeImmutable('2024-05-01')),
                    ]),
                    new PageData('/bar/x', [
                        self::makeParsedFile(physicalPath: '/bar/x.md', publishDate: new \DateTimeImmutable('2024-06-01')),
                    ]),
                    new PageData('/foo/sub/y', [
                        self::makeParsedFile(physicalPath: '/foo/sub/y.md'),
                    ]),
                ],
                'query' => $settings['query'],
                'expectedCount' => $settings['expectedCount'],
                'totalPages' => $settings['expectedCount'],
                'validator' => function (QueryResult $queryResult) {},
            ];
        }
    }

    public static function query_pages_data_pagination(): iterable
    {
        $paginationCases = [
            0 => 2,
            2 => 2,
            4 => 1,
            6 => 0
        ];
        foreach ($paginationCases as $offset => $expectedCount) {
            yield "paginated with offset $offset" => [
                'folders' => [
                    self::makeParsedFolder(physicalPath: '/foo'),
                    self::makeParsedFolder(physicalPath: '/foo/sub'),
                    self::makeParsedFolder(physicalPath: '/bar'),
                ],
                'pages' => [
                    new PageData('/foo/a', [
                        self::makeParsedFile(physicalPath: '/foo/a.md'),
                        self::makeParsedFile(physicalPath: '/foo/a.txt'),
                    ]),
                    new PageData('/foo/b', [
                        self::makeParsedFile(physicalPath: '/foo/b.md'),
                    ]),
                    new PageData('/foo/c', [
                        self::makeParsedFile(physicalPath: '/foo/c.md'),
                    ]),
                    new PageData('/foo/d', [
                        self::makeParsedFile(physicalPath: '/foo/d.md'),
                    ]),
                    new PageData('/foo/e', [
                        self::makeParsedFile(physicalPath: '/foo/e.md'),
                    ]),
                    new PageData('/bar/x', [
                        self::makeParsedFile(physicalPath: '/bar/x.md'),
                    ]),
                    new PageData('/foo/sub/y', [
                        self::makeParsedFile(physicalPath: '/foo/sub/y.md'),
                    ]),
                ],
                'query' => [
                    'folder' => '/foo',
                    'limit' => 2,
                    'offset' => $offset
                ],
                'expectedCount' => $expectedCount,
                'totalPages' => 5,
                'validator' => function (QueryResult $queryResult) {
                    $paths = array_column($queryResult->pages, 'path');
                    foreach ($paths as $p) {
                        self::assertEquals('/foo', substr($p, 0, 4));
                    }
                },
            ];
        }
    }

    public static function query_pages_data_ordering(): iterable
    {
        $orderCases = [
            'no custom order' => [
                'orderBy' => [],
                'expectedCount' => 7,
                'expectedOrder' => ['/foo/c', '/bar/x', '/foo/b', '/foo/sub/y', '/foo/d', '/foo/e', '/foo/a'],
            ],
            'by publishDate' => [
                'orderBy' => ['publishDate' => SORT_ASC],
                'expectedCount' => 7,
                'expectedOrder' => ['/foo/b', '/bar/x', '/foo/a', '/foo/sub/y', '/foo/c', '/foo/e', '/foo/d'],
            ],
            'by publishDate, descending' => [
                'orderBy' => ['publishDate' => SORT_DESC],
                'expectedCount' => 7,
                'expectedOrder' => ['/foo/d', '/foo/e', '/foo/c', '/foo/sub/y', '/bar/x', '/foo/a', '/foo/b'],
            ],
        ];
        foreach ($orderCases as $name => $settings) {
            yield "ordering for $name" => [
                'folders' => [
                    self::makeParsedFolder(physicalPath: '/foo'),
                    self::makeParsedFolder(physicalPath: '/foo/sub'),
                    self::makeParsedFolder(physicalPath: '/bar'),
                ],
                'pages' => [
                    new PageData('/foo/a', [
                        self::makeParsedFile(physicalPath: '/foo/a.md', order: 3, publishDate: new \DateTimeImmutable('2024-02-01')),
                        self::makeParsedFile(physicalPath: '/foo/a.txt', order: 7, publishDate: new \DateTimeImmutable('2024-02-01')),
                    ]),
                    new PageData('/foo/b', [
                        self::makeParsedFile(physicalPath: '/foo/b.md', order: 2, publishDate: new \DateTimeImmutable('2024-01-01')),
                    ]),
                    new PageData('/foo/c', [
                        self::makeParsedFile(physicalPath: '/foo/c.md', publishDate: new \DateTimeImmutable('2024-04-01')),
                    ]),
                    new PageData('/foo/d', [
                        self::makeParsedFile(physicalPath: '/foo/d.md', order: 6, publishDate: new \DateTimeImmutable('2024-08-01')),
                    ]),
                    new PageData('/foo/e', [
                        self::makeParsedFile(physicalPath: '/foo/e.md', order: 6, publishDate: new \DateTimeImmutable('2024-07-01')),
                    ]),
                    new PageData('/bar/x', [
                        self::makeParsedFile(physicalPath: '/bar/x.md', publishDate: new \DateTimeImmutable('2024-02-01')),
                    ]),
                    new PageData('/foo/sub/y', [
                        self::makeParsedFile(physicalPath: '/foo/sub/y.md', order: 5, publishDate: new \DateTimeImmutable('2024-03-01')),
                    ]),
                ],
                'query' => [
                    'orderBy' => $settings['orderBy'],
                ],
                'expectedCount' => $settings['expectedCount'],
                'totalPages' => $settings['expectedCount'],
                'validator' => function (QueryResult $queryResult) use ($settings) {
                    $paths = array_column($queryResult->pages, 'path');
                    self::assertEquals($settings['expectedOrder'], $paths);
                },
            ];
        }
    }

    public static function query_pages_data_exclusions(): iterable
    {
        $exclusionCases = [
            'index page is self-excluding' => [
                'exclude' => [],
                'expectedCount' => 4,
            ],
            'arbitrary page can be excluded' => [
                'exclude' => ['/foo/b'],
                'expectedCount' => 3,
            ],
            'multiple pages can be excluded' => [
                'exclude' => ['/foo/b', '/foo/c'],
                'expectedCount' => 2,
            ],
        ];
        foreach ($exclusionCases as $name => $settings) {
            yield "exclusions for $name" => [
                'folders' => [
                    self::makeParsedFolder(physicalPath: '/foo'),
                    self::makeParsedFolder(physicalPath: '/foo/sub'),
                    self::makeParsedFolder(physicalPath: '/bar'),
                ],
                'pages' => [
                    new PageData('/foo', [
                        self::makeParsedFile(physicalPath: '/foo/index.md'),
                    ]),
                    new PageData('/foo/b', [
                        self::makeParsedFile(physicalPath: '/foo/b.md'),
                    ]),
                    new PageData('/foo/c', [
                        self::makeParsedFile(physicalPath: '/foo/c.md'),
                    ]),
                    new PageData('/foo/d', [
                        self::makeParsedFile(physicalPath: '/foo/d.md'),
                    ]),
                    new PageData('/foo/e', [
                        self::makeParsedFile(physicalPath: '/foo/e.md'),
                    ]),
                    new PageData('/bar/x', [
                        self::makeParsedFile(physicalPath: '/bar/x.md'),
                    ]),
                    new PageData('/foo/sub/y', [
                        self::makeParsedFile(physicalPath: '/foo/sub/y.md'),
                    ]),
                ],
                'query' => [
                    'folder' => '/foo',
                    'exclude' => $settings['exclude'],
                ],
                'expectedCount' => $settings['expectedCount'],
                'totalPages' => $settings['expectedCount'],
                'validator' => function (QueryResult $queryResult) use ($settings) {
                    $paths = array_column($queryResult->pages, 'path');
                    foreach ($settings['exclude'] as $excluded) {
                        self::assertNotContains($excluded, $paths);
                    }
                },
            ];
        }
    }

    #[Test]
    #[DataProvider('query_pages_data')]
    #[DataProvider('query_pages_data_ordering')]
    #[DataProvider('query_pages_data_pagination')]
    #[DataProvider('query_pages_data_publication_date')]
    #[DataProvider('query_pages_data_tags_any')]
    #[DataProvider('query_pages_data_exclusions')]
    public function query_pages(array $folders, array $pages, array $query, int $expectedCount, int $totalPages, ?\Closure $validator = null): void
    {
        $cache = new YiiDbPageCache($this->conn);
        $cache->reinitialize();

        array_map($cache->writeFolder(...), $folders);
        array_map($cache->writePage(...), $pages);

        $queryResult = $cache->queryPages(...$query);

        self::assertCount($expectedCount, $queryResult);
        self::assertEquals($totalPages, $queryResult->total);

        if ($validator) {
            $validator($queryResult);
        }
    }

    #[Test]
    public function index_files_are_not_considered_children(): void
    {
        $cache = new YiiDbPageCache($this->conn);
        $cache->reinitialize();

        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/foo'));
        $cache->writeFolder(self::makeParsedFolder(physicalPath: '/foo/bar'));

        $cache->writePage(new PageData('/foo/a', [
            self::makeParsedFile(physicalPath: '/foo/a.md'),
            self::makeParsedFile(physicalPath: '/foo/a.txt'),
        ]));
        $cache->writePage(new PageData('/foo/b', [
            self::makeParsedFile(physicalPath: '/foo/b.txt'),
        ]));
        $cache->writePage(new PageData('/foo/bar/c.md', [
            self::makeParsedFile(physicalPath: '/foo/bar/c.md'),
        ]));
        $cache->writePage(new PageData('/foo/bar', [
            self::makeParsedFile(physicalPath: '/foo/bar/index.latte'),
        ]));

        // /foo/bar/index counts as a child of /foo.
        $queryResult = $cache->queryPages(folder: '/foo');
        self::assertCount(3, $queryResult);

        // /foo/bar/index does not count as a child of /foo/bar.
        $queryResult = $cache->queryPages(folder: '/foo/bar');
        self::assertCount(1, $queryResult);
    }

    /**
     * For introspecting the DB as part of test validation.
     */
    private function getPage(string $path): array
    {
        return $this->conn
            ->createCommand("SELECT * FROM page WHERE logicalPath=:logicalPath")
            ->bindParam(':logicalPath', $path)
            ->queryOne();
    }

    private function dumpPageTable(): void
    {
        var_dump($this->conn->createCommand("SELECT * FROM page")->queryAll());
    }
}
