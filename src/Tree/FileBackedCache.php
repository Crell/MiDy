<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

/**
 * @todo Actually do the file write-throug logic.
 */
class FileBackedCache implements \ArrayAccess
{
    private array $cache = [];

    public function writeFile(string $path, array $data): void
    {
        $this->cache['files'][$path] = $data;
    }

    public function updateFolder(string $path, array $data): void
    {
        $this->cache['folders'][$path] += $data;
    }

    public function addChild(string $folderPath, string $childPath): void
    {
        if (!isset($this->cache['folders'][$folderPath]['children']) || !in_array($childPath, $this->cache['folders'][$folderPath]['children'], true)) {
            $this->cache['folders'][$folderPath]['children'][] = $childPath;
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->cache);
    }

    public function &offsetGet(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            $this->offsetSet($offset, []);
        }
        return $this->cache[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->cache[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->cache['offset']);
    }
}
