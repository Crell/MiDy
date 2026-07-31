<?php

declare(strict_types=1);

namespace Crell\MiDy\Config;

use Crell\Config\Config;
use Crell\Serde\Attributes\Field;
use Traversable;

/**
 * @codeCoverageIgnore
 *
 * @implements \IteratorAggregate<string, string|int|float>
 */
#[Config('template-variables')]
readonly class TemplateVariables implements \IteratorAggregate
{
    /**
     * @param array<string, string|int|float> $variables
     */
    public function __construct(
        #[Field(flatten: true)]
        public array $variables = [],
    ) {}

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->variables);
    }
}