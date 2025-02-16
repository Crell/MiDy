<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

use Crell\MiDy\PageTree\Router\HttpQuery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HttpQueryTest extends TestCase
{
    public static function type_filter_examples(): iterable
    {
        // Ints
        yield [
            'init' => ['int' => "1", 'str' => 'B', 'float' => '3.14'],
            'method' => 'getInt',
            'key' => 'int',
            'default' => 100,
            'expected' => 1,
        ];
        yield [
            'init' => ['int' => "1", 'str' => 'B', 'float' => '3.14'],
            'method' => 'getInt',
            'key' => 'str',
            'default' => 100,
            'expected' => 100,
        ];
        yield [
            'init' => ['int' => "1", 'str' => 'B', 'float' => '3.14'],
            'method' => 'getInt',
            'key' => 'missing',
            'default' => 100,
            'expected' => 100,
        ];

        // Nums
        yield [
            'init' => ['int' => "1", 'str' => 'B', 'float' => '3.14'],
            'method' => 'getNum',
            'key' => 'int',
            'default' => 100,
            'expected' => 1,
        ];
        yield [
            'init' => ['int' => "-1", 'str' => 'B', 'float' => '3.14'],
            'method' => 'getNum',
            'key' => 'int',
            'default' => 100,
            'expected' => 100,
        ];
        yield [
            'init' => ['int' => "1", 'str' => 'B', 'float' => '3.14'],
            'method' => 'getNum',
            'key' => 'str',
            'default' => 100,
            'expected' => 100,
        ];
        yield [
            'init' => ['int' => "1", 'str' => 'B', 'float' => '3.14'],
            'method' => 'getNum',
            'key' => 'missing',
            'default' => 100,
            'expected' => 100,
        ];

        // Floats
        yield [
            'init' => ['int' => "1", 'str' => 'B', 'float' => '3.14'],
            'method' => 'getFloat',
            'key' => 'float',
            'default' => 2.1,
            'expected' => 3.14,
        ];
        yield [
            'init' => ['int' => "1", 'str' => 'B', 'float' => '3.14'],
            'method' => 'getFloat',
            'key' => 'str',
            'default' => 2.1,
            'expected' => 2.1,
        ];
        yield [
            'init' => ['int' => "1", 'str' => 'B', 'float' => '3.14'],
            'method' => 'getFloat',
            'key' => 'int',
            'default' => 2.1,
            'expected' => 1,
        ];
        yield [
            'init' => ['int' => "1", 'str' => 'B', 'float' => '3.14'],
            'method' => 'getFloat',
            'key' => 'missing',
            'default' => 2.1,
            'expected' => 2.1,
        ];

        // Strings
        yield [
            'init' => ['int' => "1", 'str' => 'B', 'float' => '3.14'],
            'method' => 'getString',
            'key' => 'str',
            'default' => 'nope',
            'expected' => 'B',
        ];
        yield [
            'init' => ['int' => "1", 'str' => 'B', 'float' => '3.14'],
            'method' => 'getString',
            'key' => 'float',
            'default' => 'nope',
            'expected' => '3.14',
        ];
        yield [
            'init' => ['int' => "1", 'str' => 'B', 'float' => 3.14],
            'method' => 'getString',
            'key' => 'float',
            'default' => 'nope',
            'expected' => 3.14,
        ];
        yield [
            'init' => ['int' => "1", 'str' => 'B', 'float' => '3.14'],
            'method' => 'getString',
            'key' => 'missing',
            'default' => 'nope',
            'expected' => 'nope',
        ];
    }

    #[Test, DataProvider('type_filter_examples')]
    public function finds_right_value(array $init, string $method, string $key, mixed $default, mixed $expected): void
    {
        $query = new HttpQuery($init);
        self::assertEquals($expected, $query->$method($key, $default));
    }

    #[Test]
    public function query_modification_is_immutable(): void
    {
        $q1 = new HttpQuery();

        $q2 = $q1->with(s: 'A', i: -5, n: 5);

        self::assertNotSame($q1, $q2);
        self::assertEquals('A', $q2->getString('s'));
        self::assertEquals('-5', $q2->getInt('i'));
        self::assertEquals('5', $q2->getNum('n'));

        self::assertNull($q1->getString('s'));
        self::assertNull($q1->getInt('i'));
        self::assertNull($q1->getNum('n'));
    }
}
