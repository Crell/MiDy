<?php

declare(strict_types=1);

namespace Crell\MiDy\Config;

use Crell\Config\Config;
use Crell\Serde\Attributes\Field;

#[Config('template-variables')]
readonly class TemplateVariables
{
    public function __construct(
        #[Field(flatten: true)]
        public array $variables = [],
    ) {}
}