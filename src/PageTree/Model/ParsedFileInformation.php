<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Model;

use Crell\MiDy\PageTree\PageInformation;

// Formerly ParsedFile, may go back to that later.
class ParsedFileInformation
{
    public function __construct(
        // Derived from file system.
        public string $logicalPath,
        public string $ext,
        public string $physicalPath,
        public int $mtime,
        public int $order,

        // Derived from filesystem, overridable from ParsedFrontMatter
        public \DateTimeImmutable $publishDate,
        public \DateTimeImmutable $lastModifiedDate,

        // Derived mostly from file type.
        public bool $routable,
        public string $pathName,

        // Probably don't need this eventually.
        public string $folder,

        // From ParsedFrontmatter
        public string $title,
        public string $summary,

        // From ParsedFrontmatter, maybe shouldn't have defaults here?
        public array $tags = [],
        public ?string $slug = null,
        public bool $hidden = false,
        public array $other = [],

        // Optional so has to be last.
        public bool $isFolder = false,
    ) {}

    public function toFileInPage(): FileInPage
    {
        return new FileInPage(
            physicalPath: $this->physicalPath,
            ext: $this->ext,
            mtime: $this->mtime,
            other: $this->other,
        );
    }
}
