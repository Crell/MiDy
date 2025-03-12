<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LogicalPathTest extends TestCase
{
    #[Test]
    public function streams_disallowed(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        LogicalPath::create('vfs://beep/boop');
    }

    #[Test]
    public function abs_allowed(): void
    {
        $path = LogicalPath::create('/foo/bar');

        self::assertEquals('/foo/bar', $path);
    }
}