<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Latte;

use Crell\MiDy\PageTree\MockPage;
use Crell\MiDy\PageTree\Page;
use Crell\MiDy\PageTree\PageTree;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class PageTreeExtensionTest extends TestCase
{
    #[Test]
    #[TestWith(['http://www.example.com/', '/foo/bar', '/foo/bar'])]
    #[TestWith(['http://www.example.com', '/foo/bar', '/foo/bar'])]
    #[TestWith(['http://www.example.com:8080', '/foo/bar', '/foo/bar'])]
    #[TestWith(['http://www.example.com', '/foo/bar', '/foo/bar?q=something', ['q' => 'something']])]
    #[TestWith(['http://www.example.com/', '/foo/bar', 'http://www.example.com/foo/bar', [], true])]
    #[TestWith(['http://www.example.com', '/foo/bar', 'http://www.example.com/foo/bar', [], true])]
    #[TestWith(['http://www.example.com:8080', '/foo/bar', 'http://www.example.com:8080/foo/bar', [], true])]
    #[TestWith(['http://www.example.com', '/foo/bar', 'http://www.example.com/foo/bar?q=something', ['q' => 'something'], true])]
    public function base_path_applied(string $base, string $path, string $expected, array $query = [], bool $full = false): void
    {
        $mockPage = new MockPage(path: $path);

        $pageTree = new class extends PageTree
        {
            public function __construct() {}
        };

        $ext = new PageTreeExtension($base, $pageTree);
        $result = $ext->pageUrl($mockPage, $query, full: $full);

        self::assertEquals($expected, $result);
    }

    #[Test]
    #[TestWith([
        'http://www.example.com/',
        new MockPage(path: '/foo', publishDate: new \DateTimeImmutable('2024-10-31')),
        'tag:www.example.com,2024-10-31:/foo',
    ])]
    #[TestWith([
        'http://www.example.com/',
        new MockPage(path: '/foo#anchor', publishDate: new \DateTimeImmutable('2024-10-31')),
        'tag:www.example.com,2024-10-31:/foo/anchor',
    ])]
    #[TestWith([
        'https://www.example.com/',
        new MockPage(path: '/foo#anchor', publishDate: new \DateTimeImmutable('2024-10-31')),
        'tag:www.example.com,2024-10-31:/foo/anchor',
    ])]
    #[TestWith([
        'http://www.example.com:8080/',
        new MockPage(path: '/foo#anchor', publishDate: new \DateTimeImmutable('2024-10-31')),
        'tag:www.example.com,2024-10-31:/foo/anchor',
    ], 'exclude port from tag')]
    public function atom_ids(string $base, Page $mockPage, string $expected): void
    {
        $pageTree = new class extends PageTree
        {
            public function __construct() {}
        };

        $ext = new PageTreeExtension($base, $pageTree);
        $result = $ext->atomId($mockPage);

        self::assertEquals($expected, $result);
    }
}
