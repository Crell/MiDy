<?php

declare(strict_types=1);

namespace Crell\MiDy;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContent;
use org\bovigo\vfs\vfsStreamDirectory;

trait FakeFilesystem
{
    protected function makeFilesystemFrom(\Closure $definition): vfsStreamContent
    {
        return vfsStream::setup('root', null, iterator_to_array($definition()));
    }

    protected function realisticStructure(): array
    {
        return [
            'empty' => [],
            'dir1' => [
                'index.md'    => '',
                'apage' => '',
                'another' => '',
                'dir2' => [
                    'subfile1' => '',
                    'subfile2' => '',
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
            'empty' => [],
            'dir1' => [
                'index.md'    => '',
                'apage.txt' => '',
                'another.md' => '',
                'dir2' => [
                    'subfile1.md' => '',
                    'subfile2.md' => '',
                ],
                'double.latte' => '',
                'double.php' => '',
            ],
            'index.md'    => '',
            'php-test.php'    => '',
            'yaml-test.yaml'    => '',
            'md-test.md'    => '',
            'latte-test.latte'    => '',
        ];
    }
}
