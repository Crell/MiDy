<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

use Latte\Engine;

readonly class Templates
{
    public function __construct(
        private Engine $engine,
        private string $templateDirectory,
    ) {}

    /**
     * @param object|array<string, mixed> $params
     */
    public function render(string $name, object|array $params = [], ?string $block = null): string
    {
        $templateFile = "$this->templateDirectory/$name.latte";
        return $this->engine->renderToString($templateFile, $params, $block);
    }
}
