<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

class TemplatePreRender
{
    public function __construct(
        public string $template,
        public array $args,
    ) {}
}
