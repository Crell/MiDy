<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;

class MarkdownLatteFileInterpreterTest extends FileInterpreterTestBase
{
    protected static array $files = [
        'basic.md' => [
            'content' => <<<END
            # Stuff
            Here
            END,
            'expectedTitle' => 'Stuff',
            'expectedPath' => '/files/basic',
        ],
        'customized.md' => [
            'content' => <<<END
            ---
            title: Custom title
            slug: custom
            ---
            # Stuff
            Here
            END,
            'expectedTitle' => 'Custom title',
            'expectedPath' => '/files/custom',
        ],
    ];

    protected function getInterpreter(): FileInterpreter
    {
        return new MarkdownLatteFileInterpreter(new MarkdownPageLoader());
    }
}
