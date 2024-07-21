<?php

declare(strict_types=1);

namespace Crell\MiDy\MarkdownDeserializer;

use Crell\MiDy\MarkdownDeserializer\Attributes\Content;
use Crell\Serde\Attributes\Field;

class MarkdownPage
{
    public function __construct(
        #[Content]
        public string $content,
        public string $title = '',
        public string $template = '',
        #[Field(flatten: true)]
        public array $other = [],
    ) {}

    public function toTemplateParameters(): array
    {
        $out = $this->other;
        $out += [
            'content' => $this->content,
            'title' => $this->title,
            'template' => $this->template,
        ];
        return $out;
    }
}
