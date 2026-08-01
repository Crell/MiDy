<?php

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\PageTree\SortOrder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[Small]
class FolderDefTest extends TestCase
{
    public static function enumExamples(): \Generator
    {
        foreach ([SortOrder::Asc, 'Asc', 'asc', 'ASC'] as $val) {
            yield [$val, SortOrder::Asc];
        }
        foreach ([SortOrder::Desc, 'Desc', 'desc', 'DESC'] as $val) {
            yield [$val, SortOrder::Desc];
        }
    }

    #[Test, DataProvider('enumExamples')]
    #[TestDox('The sort order for the folder definition file is case-insensitive.')]
    public function enum(SortOrder|string $val, SortOrder $expected): void
    {
        $f = new FolderDef($val);

        self::assertEquals($expected, $f->order);
    }
}