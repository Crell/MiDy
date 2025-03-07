<?php

declare(strict_types=1);

namespace Crell\MiDy\Path;

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
        $obj = Path::fromString($path);

        self::assertInstanceOf($expectedClass, $obj);
        self::assertEquals($path, (string)$obj);
        self::assertEquals($expectedExt, $obj->ext);
    }

}
