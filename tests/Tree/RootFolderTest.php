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
        $filePath = $this->makeFilesystemFrom($this->simpleStructure(...))->url();

        $r = new RootFolder($filePath, new FileBackedCache());

        self::assertCount(8, $r);
    }

    #[Test]
    public function correct_child_types(): void
    {
        $filePath = $this->makeFilesystemFrom($this->simpleStructure(...))->url();

        $r = new RootFolder($filePath, new FileBackedCache());

        foreach ($r as $child) {
            self::assertTrue($child instanceof Page || $child instanceof Folder);
        }
    }

}