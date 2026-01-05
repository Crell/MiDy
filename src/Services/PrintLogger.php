<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class PrintLogger implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $context = array_filter($context, static fn($val) => is_string($val) || is_numeric($val));

        $find = array_map(fn(string $key) => '{' . $key . '}', array_keys($context));
        $msg = str_replace($find, array_values($context), $message);
        printf('%s: %s' . PHP_EOL, $level, $msg);
    }
}
