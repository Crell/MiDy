<?php

declare(strict_types=1);

namespace Crell\MiDy;

use League\Flysystem\FilesystemReader;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\StorageAttributes;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use League\Flysystem\Filesystem;

class FlysystemTest extends TestCase
{

//    #[Test]
    public function deep(): void
    {
        $rootPath = realpath(__DIR__ . '/../routes');
        $adapter = new LocalFilesystemAdapter($rootPath);
        $filesystem = new Filesystem($adapter);

        $list = $filesystem
            ->listContents('/blog', FilesystemReader::LIST_DEEP)
            ->filter(fn (StorageAttributes $attributes) => $attributes->isFile());

        $fileList = $list->toArray();

        var_dump($fileList);

        self::assertCount(6, $fileList);
        self::assertTrue($filesystem->has('about.html'));

        self::assertTrue($filesystem->has('/blog/a.md'));

    }

//    #[Test]
    public function order(): void
    {
        $rootPath = realpath(__DIR__ . '/../routes');
        $adapter = new LocalFilesystemAdapter($rootPath);
        $filesystem = new Filesystem($adapter);

        $list = $filesystem
            ->listContents('/order');

        $fileList = $list->toArray();

        var_dump($fileList);

        self::assertCount(6, $fileList);
    }

//    #[Test]
    public function iterator(): void
    {
        $path = realpath(__DIR__ . '/../routes/blog');
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        var_dump(iterator_to_array($files));
    }

    #[Test]
    public function stet(): void
    {
        self::assertTrue(true);
    }
}
