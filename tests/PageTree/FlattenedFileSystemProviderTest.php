<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\FakeFilesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FlattenedFileSystemProviderTest extends TestCase
{
    use FakeFilesystem;

    private function groupedStructure(): array
    {
        return [
            '2021' => [
                'a.md' => '',
                'b.md' => '',
                'c.md' => '',
            ],
            '2022' => [
                'd.md' => '',
                'e.md' => '',
                'f.md' => '',
            ],
        ];
    }

    #[Test]
    public function basic() : void
    {
        $filePath = $this->makeFilesystemFrom($this->groupedStructure(...))->url();
        $provider = new FlattenedFileSystemProvider($filePath);
        $folder = new RootFolder('/', new ProviderMap(['/' => $provider]));

        $children = $provider->children('/', $folder);
        self::assertCount(6, $children);
    }

}
