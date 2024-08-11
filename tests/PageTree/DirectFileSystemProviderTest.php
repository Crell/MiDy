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
    public function basic_child_finding_works() : void
    {
        $filePath = $this->makeFilesystemFrom($this->realisticStructure(...))->url();

        $provider = new DirectFileSystemProvider($filePath);
        $folder = new RootFolder('/', new ProviderMap(['/' => $provider]));

        $children = $provider->children('/', $folder);
        self::assertCount(7, $children);
    }

    #[Test]
    public function multi_file_child_finding_works() : void
    {
        $filePath = $this->makeFilesystemFrom($this->multiChildStructure(...))->url();

        $provider = new DirectFileSystemProvider($filePath);
        $folder = new RootFolder('/', new ProviderMap(['/' => $provider]));

        $children = $provider->find('/*', $folder);
        self::assertCount(2, $children);
    }

    protected function multiChildStructure(): array
    {
        return [
            'double.latte' => '',
            'double.php' => '',
            'dir1' => [
                'md-test.md',
                'md-test.gif',
            ],
        ];
    }
}
