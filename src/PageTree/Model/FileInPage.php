<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Model;

class FileInPage
{
    public function __construct(
        public string $physicalPath,
        public string $ext,
        public int $mtime,
        public array $other,
    ) {}
}
