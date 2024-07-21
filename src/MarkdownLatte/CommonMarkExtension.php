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
        return new Html($this->converter->convert($s));
    }
}
