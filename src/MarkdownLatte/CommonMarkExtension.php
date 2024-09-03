<?php

declare(strict_types=1);

namespace Crell\MiDy\MarkdownLatte;

use Latte\Extension;
use Latte\Runtime\FilterInfo;
use Latte\Runtime\Html;
use League\CommonMark\ConverterInterface;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class CommonMarkExtension extends Extension
{
    public function __construct(
        private readonly ConverterInterface $converter = new GithubFlavoredMarkdownConverter(),
    ) {}

    public function getFilters(): array
    {
        return [
            'markdown' => [$this, 'markdownFilter'],
        ];
    }

    public function markdownFilter(FilterInfo $info, string $s): Html|string
    {
        if ($s === '') {
            return new Html($s);
        }
        return new Html($this->converter->convert($this->dedent($s)));
    }

    /**
     * Removes leading indentation from multiple lines.
     *
     * Shamelessly borrowed from:
     * @see https://gist.github.com/elifiner/91c6578ad70a713e90f0bfc288c7b125
     */
    private function dedent(string $str): string
    {
        $lines = explode("\n", $str);
        $parts = array_filter($lines, static fn(string $part): string => trim($part));
        $spaces = min(array_map(static function(string $part) {
            preg_match('#^ *#', $part, $matches);
            return strlen($matches[0]);
        }, $parts));
        $trimmedLines = array_map(static fn(string $part): string => substr($part, $spaces), $lines);
        return implode("\n", $trimmedLines);
    }
}
