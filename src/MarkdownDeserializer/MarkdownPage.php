<?php

declare(strict_types=1);

namespace Crell\MiDy\MarkdownDeserializer;

use Crell\MiDy\MarkdownDeserializer\Attributes\Content;
use Crell\MiDy\PageTree\MiDyBasicFrontMatter;
use Crell\MiDy\PageTree\MiDyFrontMatter;
use Crell\Serde\Attributes\Field;

class MarkdownPage implements MiDyFrontMatter
{
    public function __construct(
        #[Content]
        public string $content,
        public string $title = '',
        public string $summary = '',
        public string $template = '',
        public array $tags = [],
        public ?string $slug = null,
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

    /**
     * @todo This is gross, but the easiest way to not cache the entire contents in the route cache.  Do better later.
     */
    public function toFrontMatter(): MiDyFrontMatter
    {
        return new MiDyBasicFrontMatter(
            title: $this->title,
            summary: $this->summary,
            tags: $this->tags,
            slug: $this->slug,
        );
    }

    public function title(): string
    {
        return $this->title;
    }

    public function summary(): string
    {
        return $this->summary;
    }

    public function tags(): array
    {
        return $this->tags;
    }

    public function slug(): ?string
    {
        return $this->slug;
    }
}
