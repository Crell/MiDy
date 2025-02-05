<?php

declare(strict_types=1);

namespace Crell\MiDy\MarkdownDeserializer;

use Crell\MiDy\MarkdownDeserializer\Attributes\Content;
use Crell\MiDy\PageTree\Model\ParsedFrontmatter;
use Crell\Serde\Attributes\Field;
use DateTimeImmutable;

use function Crell\MiDy\str_extract_between;

class MarkdownPage implements ParsedFrontmatter
{
    public function __construct(
        #[Content]
        public(set) readonly string $content,
        public string $title = '',
        // This is not ideal, as it will try to re-summarize on every request if the summary is empty.
        public string $summary = '' { get => $this->summary ?: $this->summarize(); },
        public array $tags = [],
        public ?string $slug = null,
        public bool $hidden = false,
        public bool $routable = true,
        public ?DateTimeImmutable $publishDate = null,
        public ?DateTimeImmutable $lastModifiedDate = null,
        public readonly string $template = '',
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
