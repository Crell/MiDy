<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

/**
 * @todo Actually do the file write-through logic.
 */
class PathCache
{
    public function __construct(
        private readonly string $cachePath,
    ) {}

    public function getIfOlderThan(string $logicalPath, int $origFilemTime, \Closure $regenerator): FolderData
    {
        $cacheFile = $this->cacheFile($logicalPath);

        if (file_exists($cacheFile) && filemtime($cacheFile) >= $origFilemTime) {
            $data = file_get_contents($cacheFile);
            return unserialize($data, ['allowed_classes' => [FolderData::class, Page::class]]);
        }

        $data = $regenerator();
        $this->writeFolder($data, $logicalPath);
        return $data;
    }

    public function writeFolder(FolderData $folder, $logicalPath): void
    {
        file_put_contents($this->cacheFile($logicalPath), serialize($folder));
    }

    private function cacheFile(string $logicalPath): string
    {
        return $this->cachePath . '/' . $this->cacheId($logicalPath);
    }

    private function cacheId(string $path): string
    {
        return str_replace('/', '_', $path);
    }
}
