<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

readonly class RouteFile implements Linkable
{
    public function __construct(
        public string $physicalPath,
        public string $logicalPath,
        public string $ext,
        public int $mtime,
        public MiDyFrontMatter $frontmatter,
    ) {}

    public function title(): string
    {
        return $this->frontmatter->title()
            ?: ucfirst(pathinfo($this->logicalPath, PATHINFO_BASENAME));
    }

    public function path(): string
    {
        return $this->logicalPath;
    }
}
