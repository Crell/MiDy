<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

readonly class FolderRef implements Hidable
{
    public function __construct(
        public string $physicalPath,
        public string $logicalPath,
        public bool $hidden = false,
    ) {}

    public function hidden(): bool
    {
        return $this->hidden;
    }
}
