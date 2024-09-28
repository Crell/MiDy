<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\PageTree\FolderParser\FolderParser;
use Traversable;

class Folder implements Page, PageSet, \IteratorAggregate
{
    public const string IndexPageName = 'index';

    /**
     * @todo Make lazy and public with a get hook.
     */
    private FolderData $folderData;

    public function __construct(
        public readonly string $physicalPath,
        public readonly string $logicalPath,
        protected readonly FolderParser $parser,
    ) {}

    public function count(): int
    {
        return count($this->folderData());
    }

    public function routable(): bool
    {
        return $this->indexPage() !== null;
    }

    public function path(): string
    {
        return str_replace('/index', '', $this->indexPage()?->path() ?? $this->logicalPath);
    }

    public function variants(): array
    {
        return $this->indexPage()?->variants() ?? [];
    }

    public function variant(string $ext): ?Page
    {
        return $this->indexPage()?->variant($ext);
    }

    public function getTrailingPath(string $fullPath): array
    {
        return $this->indexPage()?->getTrailingPath($fullPath) ?? [];
    }

    public function title(): string
    {
        return $this->indexPage()?->title()
            ?? ucfirst(pathinfo($this->logicalPath, PATHINFO_FILENAME));
    }

    public function summary(): string
    {
        return $this->indexPage()?->summary() ?? '';
    }

    public function tags(): array
    {
        return $this->indexPage()?->tags() ?? [];
    }

    public function hasAnyTag(string ...$tags): bool
    {
        return $this->indexPage()?->hasAnyTag(...$tags) ?? false;
    }

    public function hasAllTags(string ...$tags): bool
    {
        return $this->indexPage()?->hasAllTags(...$tags) ?? false;
    }

    public function slug(): ?string
    {
        return $this->indexPage()?->slug() ?? '';
    }

    public function hidden(): bool
    {
        return $this->indexPage()?->hidden() ?? true;
    }

    public function limit(int $count): PageSet
    {
        return $this->folderData()->limit($count);
    }

    public function paginate(int $pageSize, int $pageNum = 1): Pagination
    {
        return $this->folderData()->paginate($pageSize, $pageNum);
    }

    public function all(): iterable
    {
        return $this->folderData()->all();
    }

    public function filter(\Closure $filter): PageSet
    {
        return $this->folderData()->filter($filter);
    }

    public function filterAnyTag(string ...$tags): PageSet
    {
        return $this->folderData()->filterAnyTag(...$tags);
    }

    public function filterAllTags(string ...$tags): PageSet
    {
        return $this->folderData()->filterAllTags(...$tags);
    }

    public function get(string $name): ?Page
    {
        $candidates = iterator_to_array($this->folderData()->all(), preserve_keys: true);

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
        foreach ($this->folderData()->all() as $name => $child) {
            if ($child->hidden()) {
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

    // @todo We can probably factor this method away.
    public function indexPage(): ?Page
    {
        return $this->folderData()->indexPage;
    }

    protected function folderData(): FolderData
    {
        return $this->folderData ??= $this->parser->loadFolder($this);
    }

    protected function loadFolderRef(FolderRef $ref): Folder
    {
        return new self($ref->physicalPath, $ref->logicalPath, $this->parser);
    }
}
