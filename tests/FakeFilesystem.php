<?php

declare(strict_types=1);

namespace Crell\MiDy;

use bovigo\vfs\vfsDirectory;
use bovigo\vfs\vfsStream;

trait FakeFilesystem
{
    protected function makeFilesystemFrom(\Closure $definition): vfsDirectory
    {
        return vfsStream::setup('root', null, iterator_to_array($definition()));
    }

    protected function realisticStructure(): array
    {
        return [
            'empty' => [],
            'dir1' => [
                'index.md'    => '',
                'apage.txt' => '',
                'another.md' => '',
                'dir2' => [
                    'subfile1.txt' => '',
                    'subfile2.txt' => '',
                ],
            ],
            'index.md'    => '',
            'php-test.php'    => '',
            'yaml-test.yaml'    => '',
            'md-test.md'    => '',
            'latte-test.latte'    => '',
        ];
    }

    protected function simpleStructure(): array
    {
        return [
            'index.md' => '',
            'empty' => [],
            'dir1' => [
                'index.md' => '',
                'apage.txt' => '',
                'another.md' => '',
                'dir2' => [
                    'subfile1.md' => '',
                    'subfile2.md' => '',
                ],
            ],
            'double.latte' => '',
            'double.php' => '',
            'php-test.php' => '',
            'yaml-test.yaml' => '',
            'md-test.md' => '',
            'latte-test.latte' => '',
        ];
    }
}
