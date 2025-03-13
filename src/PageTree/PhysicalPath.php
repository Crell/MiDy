<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\Path\AbsolutePath;

class PhysicalPath extends AbsolutePath
{
    public private(set) string $withoutExtension {
        get => $this->withoutExtension ??= $this->derivePathWithoutExtension();
    }

    public bool $exists {
        get => file_exists($this->path);
    }

    public private(set) \SplFileInfo $fileInfo {
        get => $this->fileInfo ??= new \SplFileInfo($this->path);
    }

    public static function create(string|self $path): PhysicalPath
    {
        // This lets us easily handle "string or PhysicalPath" arguments by doing
        // the upcasting transparently here.
        if ($path instanceof self) {
            return $path;
        }

        return self::createFromString($path);
    }

    public function contents(): string
    {
        return file_get_contents($this->path);
    }

    protected function derivePathWithoutExtension(): string
    {
        if (!$this->ext) {
            return $this->path;
        }

        return substr($this->path, 0, strpos($this->path, '.'));
    }
}
