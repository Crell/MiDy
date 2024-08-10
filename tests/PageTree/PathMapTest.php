<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PathMapTest extends TestCase
{
    #[Test, DataProvider('findForPathExamples')]
    public function find_for_path_finds_right_path(array $paths, string $path, int $expected): void
    {
        $map = new PathMap($paths);

        $result = $map->findForPath($path);

        self::assertSame($expected, $result);
    }

    public static function findForPathExamples(): iterable
    {
        $standard = [
            '/' => 1,
            '/foo' => 2,
            '/foo/bar' => 3,
        ];

        yield [
            'paths' => $standard,
            'path' => '/',
            'expected' => 1,
        ];

        yield [
            'paths' => $standard,
            'path' => '/baz',
            'expected' => 1,
        ];

        yield [
            'paths' => $standard,
            'path' => '/foo',
            'expected' => 2,
        ];

        yield [
            'paths' => $standard,
            'path' => '/foo/bar',
            'expected' => 3,
        ];

        yield [
            'paths' => $standard,
            'path' => '/foo/bar/beep',
            'expected' => 3,
        ];
    }


    #[Test, DataProvider('filterForGlobExamples')]
    public function find_for_path_glob_finds_right_paths(array $paths, string $pattern, int $expectedCount): void
    {
        $map = new PathMap($paths);

        $result = $map->filterForGlob($pattern);

        self::assertCount($expectedCount, $result);
    }

    public static function filterForGlobExamples(): iterable
    {
        $standard = [
            '/' => 1,
            '/foo' => 2,
            '/foo/bar' => 3,
            '/narf' => 4,
        ];

        yield [
            'paths' => $standard,
            'pattern' => '/**',
            'expectedCount' => 4,
        ];

        yield [
            'paths' => $standard,
            'pattern' => '/baz/**',
            'expectedCount' => 1,
        ];

        yield [
            'paths' => $standard,
            'pattern' => '/foo/**',
            'expectedCount' => 2,
        ];

        yield [
            'paths' => $standard,
            'pattern' => '/foo/bar/**',
            'expectedCount' => 1,
        ];

        yield [
            'paths' => $standard,
            'pattern' => '/foo/bar/beep/**',
            'expectedCount' => 1,
        ];
    }
}
