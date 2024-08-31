<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Traversable;

class Folder implements \Countable, \IteratorAggregate, Linkable, MultiType
{
    public const string IndexPageName = 'index';

    private FolderData $folderData;

    private ?Page $indexPage;

    public function __construct(
        public readonly string $physicalPath,
        public readonly string $logicalPath,
        protected readonly FolderParser $parser,
    ) {}

    public function count(): int
    {
        return $this->getFolderData()->count();
    }

    public function getIterator(): Traversable
    {
        /** @var FolderRef|Page $child */
        foreach ($this->getFolderData()->children as $child) {
            if (!$child->hidden) {
                yield match (get_class($child)) {
                    FolderRef::class => $this->loadFolderRef($child),
                    Page::class => $child,
                };
            }
        }
    }

    public function limitTo(string $variant): static
    {
        /** @var ?Page $page */
        $page = $this->child(self::IndexPageName);
        if (!$page) {
            return $this;
        }

        $folder = new Folder($this->physicalPath, $this->logicalPath, $this->parser);

        $folder->indexPage = $page->limitTo($variant);
        return $folder;
    }

    public function variants(): array
    {
        return $this->getIndexPage()?->variants() ?? [];
    }

    public function variant(string $ext): ?RouteFile
    {
        return $this->getIndexPage()?->variant('ext');
    }

    public function find(string $path): Page|Folder|null
    {
        $dirParts = array_filter(explode('/', $path));

        $child = $this;

        foreach ($dirParts as $pathSegment) {
            $child = $child?->child($pathSegment);
        }

        return $child;
    }

    public function children(): Traversable
    {
        return $this;
    }

    public function child(string $name): Folder|Page|null
    {
        $pathinfo = pathinfo($name);

        $child = $this->getFolderData()->children[$pathinfo['filename']] ?? null;

        if ($child instanceof FolderRef) {
            return $this->loadFolderRef($child);
        }
        if ($child && isset($pathinfo['extension'])) {
            /** @var Page $child */
            $child = $child->limitTo($pathinfo['extension']);
        }

        return $child;
    }

    public function title(): string
    {
        return $this->getIndexPage()?->title()
            ?? ucfirst(pathinfo($this->logicalPath, PATHINFO_BASENAME));
    }

    public function path(): string
    {
        return $this->logicalPath;
    }

    public function getIndexPage(): ?Page
    {
        return $this->indexPage ??= $this->child(self::IndexPageName);
    }

    protected function loadFolderRef(FolderRef $ref): Folder
    {
        return new Folder($ref->physicalPath, $ref->logicalPath, $this->parser);
    }

    protected function getFolderData(): FolderData
    {
        return $this->folderData ??= $this->parser->loadFolder($this);
    }
}
