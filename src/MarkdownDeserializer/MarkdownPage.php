<?php

declare(strict_types=1);

namespace Crell\MiDy\MarkdownDeserializer;

use Crell\MiDy\MarkdownDeserializer\Attributes\Content;
use Crell\MiDy\PageTreeDB2\BasicPageInformation;
use Crell\MiDy\PageTreeDB2\PageInformation;
use Crell\Serde\Attributes\Field;

use function Crell\MiDy\str_extract_between;

class MarkdownPage implements PageInformation
{
    public function __construct(
        #[Content]
        public(set) readonly string $content,
        public readonly string $title = '',
        // This is not ideal, as it will try to re-summarize on every request if the summary is empty.
        public private(set) string $summary = '' { get => $this->summary ?: $this->summarize(); },
        public readonly string $template = '',
        public readonly array $tags = [],
        public readonly ?string $slug = null,
        public readonly bool $hidden = false,
        #[Field(flatten: true)]
        public readonly array $other = [],
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
    public function pageInformation(): PageInformation
    {
        return new BasicPageInformation(
            title: $this->title,
            summary: $this->summary,
            tags: $this->tags,
            slug: $this->slug,
        );
    }

    private function summarize(): string
    {
        $bodySummary = str_extract_between($this->content, '<!--summary-->', '<!--/summary-->');

        if ($bodySummary) {
            return trim($bodySummary);
        }

        return '';
    }


    public function hasAnyTag(string ...$tags): bool
    {
        return (bool)array_intersect($this->tags, $tags);
    }

    public function hasAllTags(string ...$tags): bool
    {
        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                return false;
            }
        }
        return true;
    }
}
