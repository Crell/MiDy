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
        $file = trim($file, '/');

        $fileSource = file_get_contents($this->root . '/' . $file);

        if ($fileSource === false) {
            return MarkdownError::FileNotFound;
        }

        [$empty, $header, $content] = \explode('---', $fileSource);

        $document = $this->serde->deserialize($header, from: 'yaml', to: MarkdownPage::class);
        $document->{$this->documentStructure->contentField} = $content;

        return $document;
    }
}
