<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

readonly class Page
{
    public function __construct(
        private string $logicalPath,
        private array $variants,
    ) {}

    public function path(): string
    {
        return $this->logicalPath;
    }

    public function mtime(): int
    {
        $mtime = 0;
        foreach ($this->variants as $ext => $file) {
            $mtime = max($file['mtime'], $mtime);
        }
        return $mtime;
    }



}
