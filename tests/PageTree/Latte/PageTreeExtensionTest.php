<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Latte;

use Crell\MiDy\PageTree\MockPage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class PageTreeExtensionTest extends TestCase
{
    #[Test]
    #[TestWith(['http://www.example.com/', '/foo/bar', 'http://www.example.com/foo/bar'])]
    #[TestWith(['http://www.example.com', '/foo/bar', 'http://www.example.com/foo/bar'])]
    #[TestWith(['http://www.example.com:8080', '/foo/bar', 'http://www.example.com:8080/foo/bar'])]
    public function base_path_applied(string $base, string $path, string $expected): void
    {
        $mockPage = new MockPage(path: $path);

        $ext = new PageTreeExtension($base);
        $result = $ext->pageUrl($mockPage);

        self::assertEquals($expected, $result);
    }
}
