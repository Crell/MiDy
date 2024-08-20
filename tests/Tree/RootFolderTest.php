<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Crell\MiDy\FakeFilesystem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RootFolderTest extends TestCase
{
    use FakeFilesystem;

    #[Test]
    public function count_returns_correct_value(): void
    {
        // This mess is because vfsstream doesn't let you create multiple streams
        // at the same time.  Which is dumb.
        $structure = function () {
            return [
                'cache' => [],
                'data' => $this->simpleStructure(),
            ];
        };
        $vfs = $this->makeFilesystemFrom($structure);
        $filePath = $vfs->getChild('data')->url();
        $cachePath = $vfs->getChild('cache')->url();

        $r = new RootFolderWrapper($filePath, new PathCache($cachePath));

        self::assertCount(8, $r);
    }

    #[Test]
    public function correct_child_types(): void
    {
        // This mess is because vfsstream doesn't let you create multiple streams
        // at the same time.  Which is dumb.
        $structure = function () {
            return [
                'cache' => [],
                'data' => $this->simpleStructure(),
            ];
        };
        $vfs = $this->makeFilesystemFrom($structure);
        $filePath = $vfs->getChild('data')->url();
        $cachePath = $vfs->getChild('cache')->url();

        $r = new RootFolderWrapper($filePath, new PathCache($cachePath));

        foreach ($r as $child) {
            self::assertTrue($child instanceof Page || $child instanceof Folder);
        }
    }
}
