<?php

declare(strict_types=1);

namespace Crell\MiDy\MarkdownDeserializer\Attributes;

use Crell\AttributeUtils\Finalizable;
use Crell\AttributeUtils\HasSubAttributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class MarkdownField implements HasSubAttributes, Finalizable
{
    public bool $isId;
    public bool $isContent;
    public bool $isVariant;

    public function subAttributes(): array
    {
        return [
            Id::class => fn(?Id $id) => $this->isId = !is_null($id),
            Content::class => fn(?Content $body) => $this->isContent = !is_null($body),
            Variant::class => fn(?Variant $variant) => $this->isVariant = !is_null($variant),
        ];
    }

    public function finalize(): void
    {
        $this->isId ??= false;
        $this->isContent ??= false;
    }
}
