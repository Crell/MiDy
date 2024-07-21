<?php

declare(strict_types=1);

namespace Crell\MiDy\MarkdownDeserializer\Attributes;

use Crell\AttributeUtils\Finalizable;
use Crell\AttributeUtils\ParseProperties;

#[\Attribute(\Attribute::TARGET_CLASS)]
class MarkdownDocument implements ParseProperties, Finalizable
{
    public readonly string $idField;
    public readonly string $contentField;
    public readonly string $variantField;

    /**
     * @param MarkdownField[] $properties
     */
    public function setProperties(array $properties): void
    {
        // @todo Better error handling here in case someone double-marks one of these.
        foreach ($properties as $name => $p) {
            if ($p->isId) {
                $this->idField = $name;
            }
            if ($p->isContent) {
                $this->contentField = $name;
            }
            if ($p->isVariant) {
                $this->variantField = $name;
            }
        }
    }

    public function includePropertiesByDefault(): bool
    {
        return false;
    }

    public function propertyAttribute(): string
    {
        return MarkdownField::class;
    }

    public function finalize(): void
    {
        $this->idField ??= 'id';
        $this->contentField ??= 'content';
        $this->variantField ??= '';
    }
}
