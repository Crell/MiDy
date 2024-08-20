<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

readonly class Page
{
    public function __construct(
        private string $logicalPath,
        private FileBackedCache $cache,
    ) {}

    public function mtime(): int
    {
        $mtime = 0;
        foreach ($this->cache[$this->logicalPath] as $ext => $file) {
            $mtime = max($file['mtime'], $mtime);
        }
        return $mtime;
    }



}
