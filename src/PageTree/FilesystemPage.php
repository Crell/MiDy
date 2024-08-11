<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class FilesystemPage extends Page
{

    /**
     * @param array<string, string> $files
     *   A map from a file extension to a file path on disk.
     */
    public function __construct(
        string $urlPath,
        string $title,
        readonly private array $files,
    ) {
        parent::__construct($urlPath, $title);
    }
}