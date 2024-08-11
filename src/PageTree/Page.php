<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class Page
{
    /**
     * @param string $urlPath
     * @param string $title
     * @param array<string, string> $files
     *   A map from a file extension to a file path on disk.
     */
    public function __construct(
        readonly public string $urlPath,
        readonly public string $title,
    ) {}

    public function type(): PageType
    {
        return PageType::Page;
    }

    // These methods may go away, TBD.

    public function isDir(): bool
    {
        return $this->type() === PageType::Folder;
    }

    public function isFile(): bool
    {
        return $this->type() === PageType::Page;
    }

    public function title(): string
    {
        return $this->title;
    }
}

