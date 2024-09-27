<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

readonly class FolderData extends BasicPageSet
{
    public ?Page $indexPage;

    public function __construct(array $children)
    {
        // Split the index page off to a separate property, if it exists.
        $this->indexPage = $children[Folder::IndexPageName] ?? null;
        unset($children[Folder::IndexPageName]);

        parent::__construct($children);
    }
}
