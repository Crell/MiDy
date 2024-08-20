<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Exception;
use Traversable;

class FolderWrapper implements Folder
{
    private FolderData $folder;

    public function __construct(
        public readonly string $physicalPath,
        public readonly string $logicalPath,
        private PathCache $cache,
    ) {}

    public function count(): int
    {
        return $this->getFolder()->count();
    }

    public function getIterator(): Traversable
    {
        $children = iterator_to_array($this->getFolder());

        /** @var FolderRef|Page $child */
        foreach ($children as $child) {
            if ($child instanceof FolderRef) {
                yield new FolderWrapper($child->physicalPath, $child->logicalPath, $this->cache);
            } else {
                yield $child;
            }
        }
    }

    protected function getFolder(): FolderData
    {
        return $this->folder ??= $this->cache->getIfOlderThan($this->logicalPath, filemtime($this->physicalPath), $this->reindex(...));
    }

    public function reindex(): FolderData
    {
        $iter = new \FilesystemIterator($this->physicalPath);

        $toBuild = [];

        /** @var \SplFileInfo $file */
        foreach ($iter as $file) {
            if ($file->isFile()) {
                $physicalPath = $file->getPathname();
                $pathinfo = pathinfo($physicalPath);
                // @todo This gets more flexible.
                $logicalPath = ltrim($this->logicalPath, '/') . '/' . $pathinfo['filename'];

                $toBuild[$logicalPath] ??= [
                    'type' => 'page',
                    'variants' => [],
                ];

                $toBuild[$logicalPath]['variants'][$file->getExtension()] = $file;

            } else {
                $physicalPath = $file->getPathname();
                // @todo This gets more flexible.
                $logicalPath = ltrim($this->logicalPath, '/') . '/' . $file->getFilename();

                $toBuild[$logicalPath] ??= [
                    'type' => 'folder',
                    'physicalPath' => $physicalPath,
                    'data' => '',
                ];

                $toBuild[$logicalPath]['data'] = $file;
            }
        }

        $children = [];
        foreach ($toBuild as $logicalPath => $child) {
            if ($child['type'] === 'folder') {
                $children[$logicalPath] = new FolderRef($child['physicalPath'], $logicalPath);
            } else {
                $children[$logicalPath] = new Page($logicalPath, $child['variants']);
            }
        }

        return new FolderData($this->physicalPath, $this->logicalPath, $children);
    }


}
