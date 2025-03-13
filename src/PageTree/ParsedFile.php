<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\PageTree\Parser\FolderDef;
use Crell\MiDy\PageTree\Parser\Parser;

/**
 * The combined information about a file, combining data from the filesystem and frontmatter.
 */
class ParsedFile
{
    public function __construct(
        // Derived from file system.
        public LogicalPath $logicalPath,
        public string $ext,
        public PhysicalPath $physicalPath,
        public int $mtime,
        public int $order,

        // Derived from filesystem, overridable from ParsedFrontmatter
        public \DateTimeImmutable $publishDate,
        public \DateTimeImmutable $lastModifiedDate,

        // Derived mostly from file type.
        public bool $routable,
        public string $pathName,

        // Probably don't need this eventually.
        public LogicalPath $folder,

        // From ParsedFrontmatter
        public string $title,
        public string $summary,
        public array $tags,
        public ?string $slug,
        public bool $hidden,
        public array $other,
        public bool $isFolder,
    ) {}

    public static function createFromParsedData(
        \SplFileInfo $fileInfo,
        ParsedFrontmatter $frontmatter,
        LogicalPath $folderLogicalPath,
        FolderDef $folderDef,
        string $basename,
        int $order,
    ): self {
        $logicalPath = $folderLogicalPath->concat($basename);

        $pathName = $basename;
        $isFolder = false;
        if ($basename === Parser::IndexPageName) {
            // The logical path of the index page is its parent folder's path.
            $logicalPath = $folderLogicalPath;
            // The folder it should appear under is its folder's parent,
            // so that it "is" a child of that parent.
            $folderLogicalPath = $logicalPath->parent;
            // The pathName of the index page should be its folder's basename.
            $pathName = $folderLogicalPath->end;
            // And flag it as a file representing a folder.
            $isFolder = true;
        }

        return new self(
            logicalPath: $logicalPath,
            ext: $fileInfo->getExtension(),
            physicalPath: PhysicalPath::create($fileInfo->getPathname()),
            mtime: $fileInfo->getMTime(),
            order: $order,
            publishDate: $frontmatter->publishDate ?? $folderDef->defaults->publishDate ?? new \DateTimeImmutable('@' . $fileInfo->getMTime()),
            lastModifiedDate: $frontmatter->lastModifiedDate ?? $folderDef->defaults->lastModifiedDate ?? new \DateTimeImmutable('@' . $fileInfo->getMTime()),
            routable: $frontmatter->routable ?? $folderDef->defaults->routable ?? true,
            pathName: $pathName,
            folder: $folderLogicalPath,
            title: $frontmatter->title ?? $folderDef->defaults->title ?? '',
            summary: $frontmatter->summary ?? $folderDef->defaults->summary ?? '',
            tags: array_values(array_unique(array_merge($frontmatter->tags, $folderDef->defaults->tags))),
            slug: $basename,
            hidden: $frontmatter->hidden ?? $folderDef->defaults->hidden ?? false,
            other: $frontmatter->other + $folderDef->defaults->other,
            isFolder: $isFolder,
        );
    }

    /**
     * Get just the data we bother to save to the database.
     */
    public function toFile(): File
    {
        return new File(
            physicalPath: $this->physicalPath,
            ext: $this->ext,
            mtime: $this->mtime,
            other: $this->other,
        );
    }
}
