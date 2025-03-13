<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

/**
 * The limited data we need about a file, as represented within a Page record.
 */
class File implements \JsonSerializable
{
    private(set) PhysicalPath $physicalPath {
        set(PhysicalPath|string $value) => PhysicalPath::create($value);
    }

    public function __construct(
        PhysicalPath|string $physicalPath,
        public string $ext,
        public int $mtime,
        public array $other,
    ) {
        $this->physicalPath = $physicalPath;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'physicalPath' => (string)$this->physicalPath,
            'ext' => $this->ext,
            'mtime' => $this->mtime,
            'other' => $this->other,
        ];
    }
}
