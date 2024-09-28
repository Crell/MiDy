<?php

declare(strict_types=1);

namespace Crell\MiDy\TimedCache;

readonly class FilesystemTimedCache implements TimedCache
{
    public function __construct(
        private string $cachePath,
        private array $allowedClasses = [],
    ) {}

    public function get(string $key, int|\DateTimeInterface $sourceLastModified, \Closure $regenerator): mixed
    {
        // Filesystem mtime is a timestamp, so we have to use that.
        if ($sourceLastModified instanceof \DateTimeInterface) {
            $sourceLastModified = $sourceLastModified->getTimestamp();
        }

        $cacheFile = $this->cacheFile($key);

        if (file_exists($cacheFile) && filemtime($cacheFile) >= $sourceLastModified) {
            $data = file_get_contents($cacheFile);
            return $this->allowedClasses
                ? unserialize($data, ['allowed_classes' => $this->allowedClasses])
                // I know it's unsafe, but that's the user's choice to not list them.
                /** @phpstan-ignore-next-line  */
                : unserialize($data);
        }

        $data = $regenerator();
        $this->write($key, $data);
        return $data;
    }

    public function write(string $key, mixed $data): bool
    {
        return (bool)file_put_contents($this->cacheFile($key), serialize($data));
    }

    public function delete(string $key): void
    {
        $cacheFile = $this->cacheFile($key);
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    public function clear(\DateTimeInterface|int|null $olderThan = null): void
    {
        $iter = new \DirectoryIterator($this->cachePath);

        if ($olderThan instanceof \DateTimeInterface) {
            $olderThan = $olderThan->getTimestamp();
        }
        if ($olderThan !== null) {
            $iter = new \CallbackFilterIterator($iter, fn (\DirectoryIterator $f) => $f->getMTime() < $olderThan);
        }

        $iter = new \CallbackFilterIterator($iter, fn (\DirectoryIterator $f) => !$f->isDot());

        /** @var \SplFileInfo $file */
        foreach ($iter as $file) {
            unlink($file->getPathname());
        }
    }

    private function cacheFile(string $key): string
    {
        return $this->cachePath . '/' . $this->cacheId($key);
    }

    private function cacheId(string $path): string
    {
        return str_replace('/', '_', $path);
    }
}
