<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use bovigo\vfs\vfsDirectory;
use bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @todo This doesn't test file-not-found cases at all.
 */
abstract class FileInterpreterTestBase extends TestCase
{
    protected vfsDirectory $vfs;

    // Child classes should override this.
    protected static array $files = [];

    #[Before]
    protected function initFilesystemCache(): void
    {
        $this->vfs = vfsStream::setup('files', null, []);

        $basePath = $this->vfs->url() . '/';

        foreach (static::$files as $filename => $def) {
            file_put_contents($basePath . $filename, $def['content']);
        }
    }

    public static function fileProvider(): iterable
    {
        foreach (static::$files as $filename => $def) {
            yield [
                $filename,
                $def['expectedTitle'],
                $def['expectedPath']
            ];
        }
    }

    abstract protected function getInterpreter(): FileInterpreter;

    #[Test, DataProvider('fileProvider')]
    public function file_parses_correctly(string $filename, string $expectedTitle, string $expectedPath): void
    {
        $i = $this->getInterpreter();

        $mtime = time() - 20;

        $file = $this->vfs->getChild($filename)?->lastModified($mtime);

        $result = $i->map(new \SplFileInfo($file->url()), '/files');

        self::assertEquals($expectedTitle, $result->title);
        self::assertEquals($mtime, $result->mtime);
        self::assertEquals($expectedPath, $result->path());
    }
}