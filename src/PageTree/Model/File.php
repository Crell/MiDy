<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Model;

/**
 * The limited data we need about a file, as represented within a Page record.
 */
class File
{
    public function __construct(
        public string $physicalPath,
        public string $ext,
        public int $mtime,
        public array $other,
    ) {}
}
