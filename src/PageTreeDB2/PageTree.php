<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\PageTree\FolderDef;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;

class PageTree
{
    public function __construct(
        private PageCacheDB $cache,
        private Parser $parser,
    ) {}

    /**
     * Returns the Folder read-object for this path.
     */
    public function folder(string $logicalPath): ParsedFolder
    {
        $folder = $this->cache->readFolder($logicalPath);

        if (!$folder) {
            $parts = explode('/', $logicalPath);
            array_pop($parts);
            $parent = $this->folder(implode('/', $parts));
        }

        if ($this->parser->folderNeedsUpdate($folder->mtime)) {
            $this->parser->parseFolder($folder->physicalPath);
        }
    }
}
