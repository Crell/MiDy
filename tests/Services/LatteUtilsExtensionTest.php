<?php

namespace Crell\MiDy\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[Small]
class LatteUtilsExtensionTest extends TestCase
{
    public static function readingTimeExamples(): \Generator
    {
        yield [
            'wordCount' => 100,
            'minWpm' => 100,
            'maxWpm' => 100,
            'expected' => '1',
        ];
        yield [
            'wordCount' => 1000,
            'minWpm' => 100,
            'maxWpm' => 100,
            'expected' => '10',
        ];
        yield [
            'wordCount' => 1000,
            'minWpm' => 200,
            'maxWpm' => 250,
            'expected' => '4',
        ];
        yield [
            'wordCount' => 10000,
            'minWpm' => 200,
            'maxWpm' => 250,
            'expected' => '40-50',
        ];
        yield [
            'wordCount' => 1234567,
            'minWpm' => 200,
            'maxWpm' => 250,
            'expected' => '4939-6172',
        ];
    }

    #[Test, DataProvider('readingTimeExamples')]
    #[TestDox('A string of $wordCount words, with a reading speed of $minWpm to $maxWpm words per minute, can be read in $expected minutes')]
    public function readingTime(int $wordCount, int $minWpm, int $maxWpm, string $expected): void
    {
        $ext = new LatteUtilsExtension();
        $result = $ext->readingTime($this->testString($wordCount), $minWpm, $maxWpm);

        self::assertSame($expected, $result);
    }

    private function testString(int $wordCount): string
    {
        return trim(str_repeat('banana ', $wordCount));
    }
}
