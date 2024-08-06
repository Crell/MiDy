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

    protected function getStructure(): array
    {
        return [
            'cache' => [],
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