<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\FolderParser;

use Crell\MiDy\PageTree\Folder;
use Crell\MiDy\PageTree\FolderData;

/**
 * Parser for filesystem data.
 *
 * This class takes a folder and determines what its internal logical structure
 * should be.  It also caches it as appropriate.
 */
interface FolderParser
{
    /**
     * Loads the logical structure for a given folder.
     */
    public function loadFolder(Folder $folder): FolderData;
}
