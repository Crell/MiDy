<?php

declare(strict_types=1);

namespace Crell\MiDy;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContent;
use org\bovigo\vfs\vfsStreamDirectory;

trait FakeFilesystem
{
    private vfsStreamDirectory $root;

    private vfsStreamContent $dataDir;

    protected function setupFilesystem(): void
    {
        $this->root = vfsStream::setup('root', null, $this->getStructure());
        $this->dataDir = $this->root->getChild('data');
    }

    protected function usingRoot(string $name): vfsStreamContent
    {
        return $this->root->getChild($name);
    }

    protected function getStructure(): array
    {
        return [
            'multi_provider' => [
                'grouped' => [
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
                ],
                'ungrouped' => [
                    'a.md' => '',
                    'b.md' => '',
                    'c.md' => '',
                    'd.md' => '',
                    'e.md' => '',
                    'f.md' => '',
                ],
            ],
            'nested_provider' => [
                'a.txt.' => '',
                'b.txt.' => '',
                'grouped' => [
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
                ],
            ],
            'overlapping_provider' => [
                'a.txt' => '',
                'b.txt' => '',
                '2021' => [
                    'c.txt',
                    'd.txt',
                ],
                '2022' => [
                    'e.txt',
                    'f.txt',
                ],
            ],
            'data' => [
                'empty' => [],
                'dir1' => [
                    'index.md'    => <<<END
                        
                        END,
                    'apage' => '',
                    'another' => '',
                    'dir2' => [
                        'subfile1' => '',
                        'subfile2' => '',
                    ],
                ],
                'index.md'    => <<<END
                        
                END,
                'php-test.php'    => <<<END
                        
                END,
                'yaml-test.yaml'    => <<<END

                END,
                'md-test.md'    => <<<END

                END,
                'latte-test.latte'    => <<<END

                END,
            ],
        ];
    }
}
