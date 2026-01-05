<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

class TemplatePreRender
{
    /**
     * @param array<string, mixed> $args
     */
    public function __construct(
        public string $template,
        public array $args,
    ) {}
}
