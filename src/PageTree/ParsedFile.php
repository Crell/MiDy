<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class ParsedFile
{
    public function __construct(
        public string $logicalPath,
        public string $ext,
        public string $physicalPath,
        public int $mtime,
        public string $title,
        public string $folder,
        public int $order,
        public bool $hidden,
        public bool $routable,
        public \DateTimeImmutable $publishDate,
        public \DateTimeImmutable $lastModifiedDate,
        public PageInformation $frontmatter,
        public string $summary,
        public string $pathName,
        public bool $isFolder = false,
        public array $tags = [],
    ) {}

    public function __debugInfo(): ?array
    {
        return [
            'logicalPath' => $this->logicalPath,
            'physicalPath' => $this->physicalPath,
            'ext' => $this->ext,
            'title' => $this->title,
            'mtime' => $this->mtime,
        ];
    }
}
