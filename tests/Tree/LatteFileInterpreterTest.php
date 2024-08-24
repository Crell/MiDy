<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

class LatteFileInterpreterTest extends FileInterpreterTestBase
{
    protected static array $files = [
        'file.latte' => [
            'content' => 'abc123',
            'expectedTitle' => 'File',
            'expectedPath' => '/files/file',
        ],
    ];

    protected function getInterpreter(): FileInterpreter
    {
        return new LatteFileInterpreter();
    }
}
