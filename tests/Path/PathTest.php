<?php

declare(strict_types=1);

namespace Crell\MiDy\Path;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class PathTest extends TestCase
{
    #[Test]
    #[TestWith(['foo/bar', PathFragment::class])]
    #[TestWith(['/foo/bar', AbsolutePath::class])]
    #[TestWith(['http://example.com/foo/bar', StreamPath::class])]
    #[TestWith(['vfs://foo/bar', StreamPath::class])]
    #[TestWith(['foo/bar.md', PathFragment::class, 'md'])]
    #[TestWith(['/foo/bar.md', AbsolutePath::class, 'md'])]
    #[TestWith(['http://example.com/foo/bar.md', StreamPath::class, 'md'])]
    #[TestWith(['vfs://foo/bar.md', StreamPath::class, 'md'])]
    #[TestWith(['foo/bar.html.twig', PathFragment::class, 'twig'])]
    #[TestWith(['/foo/bar.html.twig', AbsolutePath::class, 'twig'])]
    #[TestWith(['http://example.com/foo/bar.html.twig', StreamPath::class, 'twig'])]
    #[TestWith(['vfs://foo/bar.html.twig', StreamPath::class, 'twig'])]
    public function correct_information_parsed(string $path, string $expectedClass, ?string $expectedExt = null): void
    {
        $obj = Path::create($path);

        self::assertInstanceOf($expectedClass, $obj);
        self::assertEquals($path, $obj);
        self::assertEquals($expectedExt, $obj->ext);
    }

    /**
     * Test cases that are not compile time constant and so cannot be used in TestWith.
     */
    public static function appendExamples(): iterable
    {
        yield ['foo/bar', 'baz', 'foo/bar/baz'];
        yield ['/foo/bar', 'baz', '/foo/bar/baz'];
        yield ['foo/bar', '/baz', 'foo/bar/baz'];
        yield ['/foo/bar', '/baz', '/foo/bar/baz'];
        yield ['/foo/bar/', '/baz', '/foo/bar/baz'];
        yield ['vfs://foo/bar', '/baz', 'vfs://foo/bar/baz'];
        yield ['http://example.com/foo/bar', '/baz', 'http://example.com/foo/bar/baz'];

        yield ['foo/bar', Path::create('baz'), 'foo/bar/baz'];
        yield ['/foo/bar', Path::create('baz'), '/foo/bar/baz'];
    }

    #[Test, DataProvider('appendExamples')]
    public function append(string $basePath, string|PathFragment $fragment, string $expected): void
    {
        $obj = Path::create($basePath);
        $new = $obj->append($fragment);

        self::assertEquals($expected, $new);
    }

    #[Test]
    #[TestWith(['foo/bar.md', 'baz'])]
    #[TestWith(['/foo/bar.md', 'baz'])]
    #[TestWith(['vfs://foo/bar.md', 'baz'])]
    #[TestWith(['foo/bar', 'vfs://baz'])]
    #[TestWith(['/foo/bar', 'vfs://baz'])]
    #[TestWith(['vfs://foo/bar', 'vfs://baz'])]
    public function append_errors(string $basePath, string $fragment): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $obj = Path::create($basePath);
        $new = $obj->append($fragment);
    }

    #[Test]
    #[TestWith(['foo/bar', 'foo'])]
    #[TestWith(['/foo/bar', '/foo'])]
    #[TestWith(['vfs://foo/bar', 'vfs://foo'])]
    #[TestWith(['foo/bar.md', 'foo'])]
    #[TestWith(['/foo/bar.md', '/foo'])]
    #[TestWith(['vfs://foo/bar.md', 'vfs://foo'])]
    #[TestWith(['', ''])]
    #[TestWith(['/', '/'])]
    #[TestWith(['vfs://foo', 'vfs://'])]
    public function parent(string $path, string $parent): void
    {
        $obj = Path::create($path);

        self::assertEquals($parent, $obj->parent);
        self::assertInstanceOf($obj::class, $obj->parent);
    }
}
