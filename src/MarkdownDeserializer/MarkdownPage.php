<?php

declare(strict_types=1);

namespace Crell\MiDy\MarkdownDeserializer;

use Crell\MiDy\MarkdownDeserializer\Attributes\Content;
use Crell\MiDy\PageTree\BasicPageInformation;
use Crell\MiDy\PageTree\PageInformation;
use Crell\Serde\Attributes\Field;

use function Crell\MiDy\str_extract_between;

class MarkdownPage implements PageInformation
{
    public function __construct(
        #[Content]
        public string $content,
        public string $title = '',
        public string $summary = '',
        public string $template = '',
        public array $tags = [],
        public ?string $slug = null,
        public bool $hidden = false,
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
    public function pageInformation(): PageInformation
    {
        return new BasicPageInformation(
            title: $this->title,
            summary: $this->summary(),
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
        if ($this->summary) {
            return $this->summary;
        }

        $bodySummary = str_extract_between($this->content, '<!--summary-->', '<!--/summary-->');

        if ($bodySummary) {
            return trim($bodySummary);
        }

        return '';
    }

    public function tags(): array
    {
        return $this->tags;
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

    public function slug(): ?string
    {
        return $this->slug;
    }

    public function hidden(): bool
    {
        return $this->hidden;
    }
}
