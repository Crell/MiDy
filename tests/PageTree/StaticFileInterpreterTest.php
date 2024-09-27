<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTree\FileInterpreter\FileInterpreter;
use Crell\MiDy\PageTree\FileInterpreter\StaticFileInterpreter;

class StaticFileInterpreterTest extends FileInterpreterTestBase
{
    protected static array $files = [
        'file.gif' => [
            'content' => 'abc123',
            'expectedTitle' => 'File',
            'expectedPath' => '/files/file',
        ],
        'page.html' => [
            'content' => '<html><title>Title here</title></html>',
            'expectedTitle' => 'Page',
            'expectedPath' => '/files/page',
        ],
    ];

    protected function getInterpreter(): FileInterpreter
    {
        return new StaticFileInterpreter(new StaticRoutes());
    }
}
