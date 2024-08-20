<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

readonly class Page
{
    public int $lastModified;


    /**
     * @param string $logicalPath
     * @param array<string, \SplFileInfo> $variants
     */
    public function __construct(
        private string $logicalPath,
        array $variants,
    ) {
        $mtime = 0;

        /**
         * @var string $ext
         * @var \SplFileInfo $file
         */
        foreach ($variants as $ext => $file) {
            $mtime = max($mtime, $file->getMTime());
        }
        $this->lastModified = $mtime;
    }

    public function path(): string
    {
        return $this->logicalPath;
    }

}
