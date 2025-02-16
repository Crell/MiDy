<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

readonly class TemplateResult
{
    public function __construct(
        public string $content,
        public string $contentType = 'text/html',
    ) {}
}
