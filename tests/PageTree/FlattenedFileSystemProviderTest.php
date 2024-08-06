<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\FakeFilesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FlattenedFileSystemProviderTest extends TestCase
{
    use FakeFilesystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupFilesystem();
    }

    #[Test]
    public function basic() : void
    {
        $filePath = $this->root->getChild('multi_provider')->url();
        $provider = new FlattenedFileSystemProvider($filePath);

        $children = $provider->children('/grouped');
        self::assertCount(6, $children);
    }

}
