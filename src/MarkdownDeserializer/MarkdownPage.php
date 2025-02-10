<?php

declare(strict_types=1);

namespace Crell\MiDy\MarkdownDeserializer;

use Crell\MiDy\MarkdownDeserializer\Attributes\Content;
use Crell\MiDy\PageTree\ParsedFrontmatter;
use Crell\Serde\Attributes\Field;
use DateTimeImmutable;

use function Crell\MiDy\str_extract_between;

class MarkdownPage implements ParsedFrontmatter
{
    private const SummaryOpenTag = '<!--summary-->';
    private const SummaryCloseTag = '<!--/summary-->';

    public function __construct(
        #[Content]
        public(set) readonly string $content,
        public ?string $title = null,
        // This is not ideal, as it will try to re-summarize on every request if the summary is empty.
        public ?string $summary = null { get => $this->summary ?: $this->summarize(); },
        public array $tags = [],
        public ?string $slug = null,
        public ?bool $hidden = null,
        public ?bool $routable = null,
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
        $bodySummary = str_extract_between($this->content, self::SummaryOpenTag, self::SummaryCloseTag);

        if ($bodySummary) {
            return trim($bodySummary);
        }

        return '';
    }
}
