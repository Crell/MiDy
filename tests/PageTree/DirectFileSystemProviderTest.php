<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\FakeFilesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DirectFileSystemProviderTest extends TestCase
{
    use FakeFilesystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupFilesystem();
    }

    #[Test]
    public function stuff() : void
    {
        $filePath = $this->dataDir->url();
        $provider = new DirectFileSystemProvider($filePath);

        $children = $provider->children('/');
        self::assertCount(7, $children);
    }

}
