<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\SetupFilesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PageRepoTest extends TestCase
{
    use SetupFilesystem;
    use SetupDB;
    use MakerUtils;


    #[Test, DoesNotPerformAssertions]
    public function reinitialize_creates_tables_if_they_dont_exist(): void
    {
        $cache = new PageRepo($this->yiiConn);

        $cache->reinitialize();

        // These will throw an exception if the tables do not exist.
        $this->db->query("SELECT 1 FROM folder")->execute();
        $this->db->query("SELECT 1 FROM page")->execute();
    }

    #[Test, DoesNotPerformAssertions]
    public function reinitialize_recreates_tables_if_they_do_exist(): void
    {
        $cache = new PageRepo($this->yiiConn);

        $this->db->exec('CREATE TABLE IF NOT EXISTS folder(fake int)');

        $cache->reinitialize();

        // These will throw an exception if the tables do not exist.
        $this->db->query("SELECT 1 FROM folder")->execute();
        $this->db->query("SELECT 1 FROM page")->execute();
    }

    #[Test]
    public function can_write_new_folder(): void
    {
        $cache = new PageRepo($this->yiiConn);

        $cache->reinitialize();

        $folder = self::makeParsedFolder(physicalPath: '/foo');

        $cache->writeFolder($folder);

        $stmt = $this->db->query("SELECT * FROM folder WHERE logicalPath='/foo'");
        $record = $stmt->fetchObject();
        self::assertEquals($folder->physicalPath, $record->physicalPath);
    }

    #[Test]
    public function can_write_updated_folder(): void
    {
        $cache = new PageRepo($this->yiiConn);

        $cache->reinitialize();

        $folder = self::makeParsedFolder(physicalPath: '/foo');
        $cache->writeFolder($folder);

        $newFolder = new ParsedFolder('/foo', '/foo', 123456, true, 'Foo2');
        $cache->writeFolder($newFolder);

        $stmt = $this->db->query("SELECT * FROM folder WHERE logicalPath='/foo'");
        $record = $stmt->fetchObject();
        self::assertEquals($newFolder->physicalPath, $record->physicalPath);
        self::assertEquals($newFolder->mtime, $record->mtime);
        self::assertEquals($newFolder->flatten, $record->flatten);
        self::assertEquals($newFolder->title, $record->title);
    }

    #[Test]
    public function can_read_folder(): void
    {
        $cache = new PageRepo($this->yiiConn);

        $cache->reinitialize();

        $folder = self::makeParsedFolder(physicalPath: '/foo');
        $cache->writeFolder($folder);

        $savedFolder = $cache->readFolder('/foo');

        self::assertEquals($folder->physicalPath, $savedFolder->physicalPath);
        self::assertEquals($folder->mtime, $savedFolder->mtime);
        self::assertEquals($folder->flatten, $savedFolder->flatten);
        self::assertEquals($folder->title, $savedFolder->title);
    }

    #[Test]
    public function returns_null_for_missing_folder(): void
    {
        $cache = new PageRepo($this->yiiConn);

        $cache->reinitialize();

        $savedFolder = $cache->readFolder('/foo');

        self::assertNull($savedFolder);
    }

    #[Test]
    public function can_delete_folder(): void
    {
        $cache = new PageRepo($this->yiiConn);
        $cache->reinitialize();

        $folder = self::makeParsedFolder(physicalPath: '/foo');
        $cache->writeFolder($folder);

        $cache->deleteFolder('/foo');

        $stmt = $this->db->query("SELECT * FROM folder WHERE logicalPath='/foo'");
        $record = $stmt->fetchObject();

        self::assertFalse($record);
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
                foreach ($page->files as $file) {
                    self::assertFalse($file->isFolder);
                    self::assertFalse($file->hidden);
                }
                self::assertCount(1, $page->files);
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
                foreach ($page->files as $file) {
                    self::assertFalse($file->isFolder);
                    self::assertFalse($file->hidden);
                }
                self::assertCount(2, $page->files);
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
                $page = $test->getPage('/foo/test');
                self::assertFalse((bool)$page['hidden']);
            },
            'validation' => function (PageRecord $page) {
                foreach ($page->files as $file) {
                    self::assertFalse($file->isFolder);
                }
                self::assertCount(3, $page->files);
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
                $page = $test->getPage('/foo/test');
                self::assertTrue((bool)$page['hidden']);
            },
            'validation' => function (PageRecord $page) {
                foreach ($page->files as $file) {
                    self::assertFalse($file->isFolder);
                }
                self::assertCount(3, $page->files);
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
                foreach ($page->files as $file) {
                    self::assertFalse($file->isFolder);
                }
                self::assertCount(3, $page->files);
            },
        ];
    }

    #[Test, DataProvider('page_data')]
    public function page_save_and_load(ParsedFolder $folder, array $files, string $pagePath, \Closure $dbValidation, \Closure $validation): void
    {
        $cache = new PageRepo($this->yiiConn);
        $cache->reinitialize();

        $cache->writeFolder($folder);

        $page = new PageRecord($pagePath, $folder->logicalPath, $files);
        $cache->writePage($page);

        $dbValidation($this);

        $record = $cache->readPage($pagePath);

        self::assertEquals('/foo/test', $page->logicalPath);
        self::assertEquals('/foo', $page->folder);
        foreach ($page->files as $file) {
            self::assertEquals($pagePath, $file->logicalPath);
        }

        $validation($record);
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
                new PageRecord('/foo/a', '/foo', [
                    self::makeParsedFile(physicalPath: '/foo/a.md'),
                    self::makeParsedFile(physicalPath: '/foo/a.txt'),
                ]),
                new PageRecord('/foo/b', '/foo', [
                    self::makeParsedFile(physicalPath: '/foo/b.md'),
                ]),
                new PageRecord('/bar/c', '/bar', [
                    self::makeParsedFile(physicalPath: '/bar/c.md'),
                ]),
            ],
            'query' => [
                'folder' => '/foo'
            ],
            'expectedCount' => 2,
            'totalPages' => 2,
            'validator' => function (QueryResult $queryResult) {
                $paths = array_column($queryResult->pages, 'logicalPath');
                self::assertContains('/foo/a', $paths);
                self::assertContains('/foo/b', $paths);
            },
        ];

        yield 'search by folder, deep' => [
            'folders' => [
                self::makeParsedFolder(physicalPath: '/foo'),
                self::makeParsedFolder(physicalPath: '/foo/sub'),
                self::makeParsedFolder(physicalPath: '/bar'),
            ],
            'pages' => [
                new PageRecord('/foo/a', '/foo', [
                    self::makeParsedFile(physicalPath: '/foo/a.md'),
                    self::makeParsedFile(physicalPath: '/foo/a.txt'),
                ]),
                new PageRecord('/foo/b', '/foo', [
                    self::makeParsedFile(physicalPath: '/foo/b.md'),
                ]),
                new PageRecord('/bar/c', '/bar', [
                    self::makeParsedFile(physicalPath: '/bar/c.md'),
                ]),
                new PageRecord('/foo/sub/y', '/foo/sub', [
                    self::makeParsedFile(physicalPath: '/foo/sub/y.md'),
                ]),
            ],
            'query' => [
                'folder' => '/foo',
                'deep' => true,
            ],
            'expectedCount' => 3,
            'totalPages' => 3,
            'validator' => function (QueryResult $queryResult) {
                $paths = array_column($queryResult->pages, 'logicalPath');
                self::assertContains('/foo/a', $paths);
                self::assertContains('/foo/b', $paths);
                self::assertContains('/foo/sub/y', $paths);
            },
        ];

        $hiddenPages = [
            new PageRecord('/foo/a', '/foo', [
                self::makeParsedFile(physicalPath: '/foo/a.md', hidden: true),
                self::makeParsedFile(physicalPath: '/foo/a.txt', hidden: true),
            ]),
            new PageRecord('/foo/b', '/foo', [
                self::makeParsedFile(physicalPath: '/foo/b.md'),
                self::makeParsedFile(physicalPath: '/foo/b.txt', hidden: true),
            ]),
            new PageRecord('/bar/c', '/bar', [
                self::makeParsedFile(physicalPath: '/bar/c.md', hidden: true),
            ]),
            new PageRecord('/foo/sub/y', '/foo/sub', [
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
                $paths = array_column($queryResult->pages, 'logicalPath');
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
                $paths = array_column($queryResult->pages, 'logicalPath');
                self::assertContains('/foo/a', $paths);
                self::assertContains('/foo/b', $paths);
                self::assertContains('/bar/c', $paths);
                self::assertContains('/foo/sub/y', $paths);
            },
        ];

        $routablePages = [
            new PageRecord('/foo/a', '/foo', [
                self::makeParsedFile(physicalPath: '/foo/a.md', routable: false),
                self::makeParsedFile(physicalPath: '/foo/a.txt', routable: false),
            ]),
            new PageRecord('/foo/b', '/foo', [
                self::makeParsedFile(physicalPath: '/foo/b.md'),
                self::makeParsedFile(physicalPath: '/foo/b.txt', routable: false),
            ]),
            new PageRecord('/bar/c', '/bar', [
                self::makeParsedFile(physicalPath: '/bar/c.md', routable: false),
            ]),
            new PageRecord('/foo/sub/y', '/foo/sub', [
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
                $paths = array_column($queryResult->pages, 'logicalPath');
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
                $paths = array_column($queryResult->pages, 'logicalPath');
                self::assertContains('/foo/a', $paths);
                self::assertContains('/foo/b', $paths);
                self::assertContains('/bar/c', $paths);
                self::assertContains('/foo/sub/y', $paths);
            },
        ];

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
                    new PageRecord('/foo/a', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/a.md', tags: ['A', 'B']),
                        self::makeParsedFile(physicalPath: '/foo/a.txt', tags: ['B', 'C']),
                    ]),
                    new PageRecord('/foo/b', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/b.md', tags: ['D']),
                    ]),
                    new PageRecord('/foo/c', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/c.md'),
                    ]),
                    new PageRecord('/foo/d', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/d.md', tags: ['A', 'C']),
                    ]),
                    new PageRecord('/foo/e', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/e.md'),
                    ]),
                    new PageRecord('/bar/x', '/bar', [
                        self::makeParsedFile(physicalPath: '/bar/x.md', tags: ['B']),
                    ]),
                    new PageRecord('/foo/sub/y', '/foo/sub', [
                        self::makeParsedFile(physicalPath: '/foo/sub/y.md'),
                    ]),
                ],
                'query' => [
                    'anyTag' => $settings['tagsToFind'],
                ],
                'expectedCount' => $settings['expectedCount'],
                'totalPages' => $settings['expectedCount'],
                'validator' => function (QueryResult $queryResult) {
                    $paths = array_column($queryResult->pages, 'logicalPath');

                },
            ];
        }

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
                    new PageRecord('/foo/a', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/a.md', publishDate: new \DateTimeImmutable('2024-01-01')),
                        self::makeParsedFile(physicalPath: '/foo/a.txt', publishDate: new \DateTimeImmutable('2024-02-01')),
                    ]),
                    new PageRecord('/foo/b', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/b.md'),
                    ]),
                    new PageRecord('/foo/c', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/c.md', publishDate: new \DateTimeImmutable('2024-03-01')),
                    ]),
                    new PageRecord('/foo/d', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/d.md', publishDate: new \DateTimeImmutable('2024-04-01')),
                    ]),
                    new PageRecord('/foo/e', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/e.md', publishDate: new \DateTimeImmutable('2024-05-01')),
                    ]),
                    new PageRecord('/bar/x', '/bar', [
                        self::makeParsedFile(physicalPath: '/bar/x.md', publishDate: new \DateTimeImmutable('2024-06-01')),
                    ]),
                    new PageRecord('/foo/sub/y', '/foo/sub', [
                        self::makeParsedFile(physicalPath: '/foo/sub/y.md'),
                    ]),
                ],
                'query' => $settings['query'],
                'expectedCount' => $settings['expectedCount'],
                'totalPages' => $settings['expectedCount'],
                'validator' => function (QueryResult $queryResult) {},
            ];
        }

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
                    new PageRecord('/foo/a', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/a.md'),
                        self::makeParsedFile(physicalPath: '/foo/a.txt'),
                    ]),
                    new PageRecord('/foo/b', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/b.md'),
                    ]),
                    new PageRecord('/foo/c', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/c.md'),
                    ]),
                    new PageRecord('/foo/d', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/d.md'),
                    ]),
                    new PageRecord('/foo/e', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/e.md'),
                    ]),
                    new PageRecord('/bar/x', '/bar', [
                        self::makeParsedFile(physicalPath: '/bar/x.md'),
                    ]),
                    new PageRecord('/foo/sub/y', '/foo/sub', [
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
                    $paths = array_column($queryResult->pages, 'logicalPath');
                    foreach ($paths as $p) {
                        self::assertEquals('/foo', substr($p, 0, 4));
                    }
                },
            ];
        }


        $orderCases = [
            'no custom order' => [
                'orderBy' => [],
                'expectedCount' => 7,
                'expectedOrder' => ['/bar/x', '/foo/c', '/foo/b', '/foo/sub/y', '/foo/d', '/foo/e', '/foo/a'],
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
                    new PageRecord('/foo/a', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/a.md', order: 3, publishDate: new \DateTimeImmutable('2024-02-01')),
                        self::makeParsedFile(physicalPath: '/foo/a.txt', order: 7, publishDate: new \DateTimeImmutable('2024-02-01')),
                    ]),
                    new PageRecord('/foo/b', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/b.md', order: 2, publishDate: new \DateTimeImmutable('2024-01-01')),
                    ]),
                    new PageRecord('/foo/c', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/c.md', publishDate: new \DateTimeImmutable('2024-04-01')),
                    ]),
                    new PageRecord('/foo/d', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/d.md', order: 6, publishDate: new \DateTimeImmutable('2024-08-01')),
                    ]),
                    new PageRecord('/foo/e', '/foo', [
                        self::makeParsedFile(physicalPath: '/foo/e.md', order: 6, publishDate: new \DateTimeImmutable('2024-07-01')),
                    ]),
                    new PageRecord('/bar/x', '/bar', [
                        self::makeParsedFile(physicalPath: '/bar/x.md', publishDate: new \DateTimeImmutable('2024-02-01')),
                    ]),
                    new PageRecord('/foo/sub/y', '/foo/sub', [
                        self::makeParsedFile(physicalPath: '/foo/sub/y.md', order: 5, publishDate: new \DateTimeImmutable('2024-03-01')),
                    ]),
                ],
                'query' => [
                    'orderBy' => $settings['orderBy'],
                ],
                'expectedCount' => $settings['expectedCount'],
                'totalPages' => $settings['expectedCount'],
                'validator' => function (QueryResult $queryResult) use ($settings) {
                    $paths = array_column($queryResult->pages, 'logicalPath');
                    self::assertEquals($settings['expectedOrder'], $paths);
                },
            ];
        }
    }

    #[Test, DataProvider('query_pages_data')]
    public function query_pages(array $folders, array $pages, array $query, int $expectedCount, int $totalPages, \Closure $validator): void
    {
        $cache = new PageRepo($this->yiiConn);
        $cache->reinitialize();

        array_map($cache->writeFolder(...), $folders);
        array_map($cache->writePage(...), $pages);

        $queryResult = $cache->queryPages(...$query);

        self::assertCount($expectedCount, $queryResult);
        self::assertEquals($totalPages, $queryResult->total);

        $validator($queryResult);
    }

    /**
     * For introspecting the DB as part of test validation.
     */
    private function getPage(string $path): array
    {
        return $this->yiiConn
            ->createCommand("SELECT * FROM page WHERE logicalPath=:logicalPath")
            ->bindParam(':logicalPath', $path)
            ->queryOne();
    }

    private function dumpPageTable(): void
    {
        var_dump($this->yiiConn->createCommand("SELECT * FROM page")->queryAll());
    }

}
