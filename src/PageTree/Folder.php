<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\PageTree\FolderParser\FolderParser;
use Traversable;

class Folder implements Page, PageSet, \IteratorAggregate
{
    public const string IndexPageName = 'index';

    private FolderData $folderData { get => $this->folderData ??= $this->parser->loadFolder($this); }
    public ?Page $indexPage { get => $this->folderData->indexPage; }

    public private(set) string $title {
        get => $this->title ??=
            $this->indexPage?->title
            ?? ucfirst(pathinfo($this->logicalPath, PATHINFO_FILENAME));
        }
    public private(set) string $summary { get => $this->summary ??= $this->indexPage?->summary ?? ''; }
    public private(set) array $tags { get => $this->tags ??= $this->indexPage?->tags ?? []; }
    public private(set) string $slug { get => $this->slug = $this->indexPage->slug ?? ''; }
    public private(set) bool $hidden { get => $this->hidden = $this->indexPage?->hidden ?? true; }

    public bool $routable { get => $this->indexPage !== null; }
    public private(set) string $path { get => $this->path ??= str_replace('/index', '', $this->indexPage?->path ?? $this->logicalPath); }

    public function __construct(
        public readonly string $physicalPath,
        public readonly string $logicalPath,
        protected readonly FolderParser $parser,
    ) {}

    public function count(): int
    {
        return count($this->folderData);
    }

    public function variants(): array
    {
        return $this->indexPage?->variants() ?? [];
    }

    public function variant(string $ext): ?Page
    {
        return $this->indexPage?->variant($ext);
    }

    public function getTrailingPath(string $fullPath): array
    {
        return $this->indexPage?->getTrailingPath($fullPath) ?? [];
    }

    public function hasAnyTag(string ...$tags): bool
    {
        return $this->indexPage?->hasAnyTag(...$tags) ?? false;
    }

    public function hasAllTags(string ...$tags): bool
    {
        return $this->indexPage?->hasAllTags(...$tags) ?? false;
    }

    public function limit(int $count): PageSet
    {
        return $this->folderData->limit($count);
    }

    public function paginate(int $pageSize, int $pageNum = 1): Pagination
    {
        return $this->folderData->paginate($pageSize, $pageNum);
    }

    public function all(): iterable
    {
        return $this->folderData->all();
    }

    public function filter(\Closure $filter): PageSet
    {
        return $this->folderData->filter($filter);
    }

    public function filterAnyTag(string ...$tags): PageSet
    {
        return $this->folderData->filterAnyTag(...$tags);
    }

    public function filterAllTags(string ...$tags): PageSet
    {
        return $this->folderData->filterAllTags(...$tags);
    }

    public function get(string $name): ?Page
    {
        $candidates = iterator_to_array($this->folderData->all(), preserve_keys: true);

        $info = pathinfo($name);

        /** @var ?Page $files */
        $files = $candidates[$info['filename']] ?? null;
        if ($files instanceof FolderRef) {
            return $this->loadFolderRef($files);
        }
        if ($info['extension'] ?? false) {
            return $files?->variant($info['extension']);
        }
        return $files;
    }

    public function getIterator(): Traversable
    {
        /**
         * @var string $name
         * @var Hidable $child
         */
        foreach ($this->folderData->all() as $name => $child) {
            if ($child->hidden) {
                continue;
            }
            yield $name => match (true) {
                $child instanceof Page => $child,
                $child instanceof FolderRef => $this->loadFolderRef($child),
            };
        }
    }

    public function find(string $path): ?Page
    {
        $dirParts = array_filter(explode('/', $path));

        $child = $this;

        foreach ($dirParts as $pathSegment) {
            $child = $child?->get($pathSegment);
        }

        return $child;
    }

    /**
     * Combines all children of this folder and their children, recursively, into a single PageSet.
     *
     * This operation is done lazily, so that you can filter the result and have it not be
     * quite so expensive.  Still, use this operation sparingly.
     */
    public function descendants(bool $visibleOnly = true): PageSet
    {
        $generator = function () use ($visibleOnly) {
            $data = $visibleOnly
                ? $this
                : $this->all();
            foreach ($data as $id => $page) {
                if ($page instanceof Folder) {
                    yield from $page->descendants($visibleOnly);
                } elseif ($page instanceof FolderRef) {
                    yield from $this->loadFolderRef($page)->descendants($visibleOnly);
                } else {
                    yield $id => $page;
                }
            }
        };
        return new BasicPageSet($generator());
    }

    protected function loadFolderRef(FolderRef $ref): Folder
    {
        return new self($ref->physicalPath, $ref->logicalPath, $this->parser);
    }
}
