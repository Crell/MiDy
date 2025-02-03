<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Model;

use Crell\MiDy\PageTree\Parser\Parser;

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

    public static function createFromParsedData(
        \SplFileInfo $fileInfo,
        ParsedFrontmatter $frontmatter,
        string $folderLogicalPath,
        string $basename,
        int $order,
    ): self {
        $logicalPath = rtrim($folderLogicalPath, '/') . '/' . $basename;

        $pathName = $basename;
        $isFolder = false;
        if ($basename === Parser::IndexPageName) {
            // The logical path of the index page is its parent folder's path.
            $logicalPath = $folderLogicalPath;
            // The folder it should appear under is its folder's parent,
            // so that it "is" a child of that parent.
            $folderLogicalPath = dirname($folderLogicalPath);
            // The pathName of the index page should be its folder's basename.
            $folderParts = \explode('/', $folderLogicalPath);
            $pathName = array_pop($folderParts);
            // And flag it as a file representing a folder.
            $isFolder = true;
        }

        return new self(
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            physicalPath: $fileInfo->getPathname(),
            mtime: $fileInfo->getMTime(),
            order: $order,
            publishDate: $frontmatter->publishDate ?? new \DateTimeImmutable('@' . $fileInfo->getMTime()),
            lastModifiedDate: $frontmatter->lastModifiedDate ?? new \DateTimeImmutable('@' . $fileInfo->getMTime()),
            routable: $frontmatter->routable,
            pathName: $pathName,
            folder: $folderLogicalPath,
            title: $frontmatter->title,
            summary: $frontmatter->summary,
            tags: $frontmatter->tags,
            slug: $basename,
            hidden: $frontmatter->hidden,
            other: $frontmatter->other,
            isFolder: $isFolder,
        );
    }

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
