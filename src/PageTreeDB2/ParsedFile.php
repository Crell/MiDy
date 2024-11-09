<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\PageTree\PageInformation;

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
    ) {}
}
