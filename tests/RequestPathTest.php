<?php

declare(strict_types=1);

namespace Crell\MiDy;

use Crell\MiDy\Router\RequestPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RequestPathTest extends TestCase
{
    public static function paths(): iterable
    {
        yield [
            'path' => '/blog/blog-three',
            'expectedExt' => '*',
            'expectedPrefixes' => [
                '/blog/blog-three',
                '/blog',
                '/',
            ],
            'expectedNormalizedPath' => '/blog/blog-three',
        ];
        yield [
            'path' => '/blog/foo/bar/baz.html',
            'expectedExt' => 'html',
            'expectedPrefixes' => [
                '/blog/foo/bar/baz',
                '/blog/foo/bar',
                '/blog/foo',
                '/blog',
                '/',
            ],
            'expectedNormalizedPath' => '/blog/foo/bar/baz',
        ];
    }

    #[Test, DataProvider('paths')]
    public function stuff(string $path, string $expectedExt, array $expectedPrefixes, string $expectedNormalizedPath): void
    {
        $requestPath = new RequestPath($path);

        self::assertEquals($expectedExt, $requestPath->ext);
        self::assertEquals($expectedPrefixes, $requestPath->prefixes);
        self::assertEquals($expectedNormalizedPath, $requestPath->normalizedPath);
    }
}
