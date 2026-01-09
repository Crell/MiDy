<?php

declare(strict_types=1);

namespace Crell\MiDy\Config;

use Crell\Config\Config;
use Crell\Serde\Attributes\Field;

/**
 * @codeCoverageIgnore
 */
#[Config('template-variables')]
readonly class TemplateVariables
{
    /**
     * @param array<string, string|int|float> $variables
     */
    public function __construct(
        #[Field(flatten: true)]
        public array $variables = [],
    ) {}
}