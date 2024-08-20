<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Traversable;

class Folder implements \Countable, \IteratorAggregate
{
    public function __construct(
        protected readonly string $physicalPath,
        protected readonly string $logicalPath,
        private FileBackedCache $cache,
    ) {}

    public function getIterator(): Traversable
    {
        $this->reindex();
        foreach ($this->cache['folders'][$this->logicalPath]['children'] as $childPath) {
            if (isset($this->cache['folders'][$childPath])) {
                // It's a child folder.
                yield new Folder($this->cache['folders'][$childPath]['physicalPath'], $childPath, $this->cache);
            } elseif (isset($this->cache['files'][$childPath])) {
                // It's a child page.
                yield new Page($childPath, $this->cache);
            } {
                // @todo Error handling.
            }
        }
    }

    public function count(): int
    {
        $this->reindex();
        return count($this->cache['folders'][$this->logicalPath]['children']);
    }

    public function reindex(): void
    {
        $iter = new \FilesystemIterator($this->physicalPath);
        /** @var \SplFileInfo $file */
        foreach ($iter as $file) {
            if ($file->isFile()) {
                $physicalPath = $file->getPathname();
                $pathinfo = pathinfo($physicalPath);
                // @todo This gets more flexible.
                $logicalPath = ltrim($this->logicalPath, '/') . '/' . $pathinfo['filename'];

                $this->cache['files'][$logicalPath][$file->getExtension()] = [
                    'physicalPath' => $physicalPath,
                    'name' => $file->getFilename(),
                    'mtime' => $file->getMTime(),
                ];
                $this->cache->addChild($this->logicalPath, $logicalPath);
            } else {
                $physicalPath = $file->getPathname();
                // @todo This gets more flexible.
                $logicalPath = ltrim($this->logicalPath, '/') . '/' . $file->getFilename();
                $this->cache['folders'][$logicalPath] = [
                    'physicalPah' => $physicalPath,
                    'name' => $file->getFilename(),
                    'mtime' => $file->getMTime(),
                ];
                $this->cache->addChild($this->logicalPath, $logicalPath);
            }
        }
    }
}
