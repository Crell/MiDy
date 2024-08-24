<?php

declare(strict_types=1);

namespace Crell\MiDy\MarkdownDeserializer;


use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\MiDy\MarkdownDeserializer\Attributes\MarkdownDocument;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;

class MarkdownPageLoader
{
    private readonly MarkdownDocument $documentStructure;

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

    private function extractFrontMatter(string $source)
    {
        // There is no header, so fall back to defaults.
        if (!str_starts_with($source, '---')) {
            // If the file begins with an H1, assume that's the title and split it off.
            // @todo Should the h1 be included or no?
            if (str_starts_with($source, '# ')) {
                $firstNewline = strpos($source, PHP_EOL);
                $title = trim(substr($source, 2, $firstNewline - 2));
                $content = trim(substr($source, $firstNewline));
                return ['title: ' . $title, $content];
            }
            // Otherwise it's just a raw markdown file, return as is.
            return ['', $source];
        }

        $withoutLeadingHeaderStart = substr($source, 4);

        $endHeaderPos = strpos($withoutLeadingHeaderStart, '---');

        $header = substr($withoutLeadingHeaderStart, 0, $endHeaderPos);
        $content = substr($withoutLeadingHeaderStart, $endHeaderPos + 4);

        return [$header, $content];

    }
}
