<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Crell\MiDy\ClassFinder;

class PhpFileInterpreterTest extends FileInterpreterTestBase
{
    protected static array $files = [
        'basic.php' => [
            'content' => <<<END
                <?php
                
                class Foo {}
            END,
            'expectedTitle' => 'Basic',
            'expectedPath' => '/files/basic',
        ],
        'attributed.php' => [
            'content' => <<<END
                <?php
                use Crell\MiDy\Tree\Attributes\PageRoute;
                
                #[PageRoute(title: "Custom title", slug: "beep")]
                class Bar {}
            END,
            'expectedTitle' => 'Custom title',
            'expectedPath' => '/files/beep',
        ],
    ];

    protected function getInterpreter(): FileInterpreter
    {
        return new PhpFileInterpreter(new ClassFinder());
    }
}
