<?php

declare(strict_types=1);

namespace Crell\MiDy\MarkdownDeserializer;


use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\MiDy\MarkdownDeserializer\Attributes\MarkdownDocument;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;

use function Crell\MiDy\str_extract_between;

class MarkdownPageLoader
{
    private readonly MarkdownDocument $documentStructure;

    /**
     * @param string $root
     *   The path on disk relative to which any file paths should be evaluated.
     *   A file path that uses a file stream URL will ignore this value.
     *   If not specified, it will default to the current working directory,
     *   which is usually the directory of the starting script.
     */
    public function __construct(
        private string $root = '',
        protected readonly Serde $serde = new SerdeCommon(),
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {
        if (!$this->root) {
            $this->root = getcwd();
        }

        $this->documentStructure = $this->analyzer->analyze(MarkdownPage::class, MarkdownDocument::class);
    }

    public function load(string $file): MarkdownPage|MarkdownError
    {
        if (!str_contains($file, '://')) {
            $file = $this->root . '/' . trim($file, '/');
        }

        $fileSource = file_get_contents($file);

        if ($fileSource === false) {
            return MarkdownError::FileNotFound;
        }

        [$header, $content] = $this->extractFrontMatter($fileSource);

        $document = $this->serde->deserialize($header, from: 'yaml', to: MarkdownPage::class);
        $document->{$this->documentStructure->contentField} = $content;

        return $document;
    }

    /**
     * @param string $source
     * @return array{header: string, content: string}
     */
    private function extractFrontMatter(string $source): array
    {
        $frontmatter = str_extract_between($source, '---', '---');

        if ($frontmatter) {
            // Add 6 to account for the delimiters themselves.
            // Trim to get rid of leading whitespace.
            return [trim($frontmatter), trim(substr($source, strlen($frontmatter) + 6))];
        }

        // If there's no front matter, but is an H1 markdown tag, assume that's the title.
        if (str_starts_with($source, '# ')) {
            $firstNewline = strpos($source, PHP_EOL) ?: strlen($source);
            $title = trim(substr($source, 2, $firstNewline - 2));
            $content = trim(substr($source, $firstNewline));
            return ['title: ' . $title, $content];
        }
        // Otherwise it's just a raw markdown file, return as is.
        return ['', $source];
    }
}
