<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\FakeFilesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DirectFileSystemProviderTest extends TestCase
{
    use FakeFilesystem;

    #[Test]
    public function basic() : void
    {
        $filePath = $this->makeFilesystemFrom($this->realisticStructure(...))->url();

        $provider = new DirectFileSystemProvider($filePath);

        $children = $provider->children('/');
        self::assertCount(7, $children);
    }
}
