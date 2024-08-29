<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Crell\MiDy\TimedCache\TimedCache;
use Traversable;

class Folder implements \Countable, \IteratorAggregate, Linkable, MultiType
{
    private const string StripPrefix = '/^([\d_-]+)_(.*)/m';

    private FolderData $folder;

    private ?Page $indexPage;

    public function __construct(
        public readonly string $physicalPath,
        public readonly string $logicalPath,
        protected readonly TimedCache $cache,
        protected readonly FileInterpreter $interpreter,
    ) {}

    public function count(): int
    {
        return $this->getFolder()->count();
    }

    public function getIterator(): Traversable
    {
        /** @var FolderRef|Page $child */
        foreach ($this->getFolder()->children as $child) {
            if ($child instanceof FolderRef) {
                yield new Folder($child->physicalPath, $child->logicalPath, $this->cache, $this->interpreter);
            } else {
                yield $child;
            }
        }
    }

    public function limitTo(string $variant): static
    {
        /** @var ?Page $page */
        $page = $this->child('index');
        if (!$page) {
            return $this;
        }

        $folder = new Folder($this->physicalPath, $this->logicalPath, $this->cache, $this->interpreter);

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

        $child = $this->getFolder()->children[$pathinfo['filename']] ?? null;

        if ($child instanceof FolderRef) {
            return new Folder($child->physicalPath, $child->logicalPath, $this->cache, $this->interpreter);
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
        return $this->indexPage ??= $this->child('index');
    }

    protected function getFolder(): FolderData
    {
        return $this->folder ??= $this->cache->get($this->logicalPath, filemtime($this->physicalPath), $this->reindex(...));
    }

    protected function reindex(): FolderData
    {
        $iter = new \FilesystemIterator($this->physicalPath);

        $toBuild = [];

        /** @var \SplFileInfo $file */
        foreach ($iter as $file) {
            if ($file->isFile()) {
                // SPL is so damned stupid...
                [$basename, $order] = $this->parseName($file->getBasename('.' . $file->getExtension()));

                $routeFile = $this->interpreter->map($file, $this->logicalPath, $basename);

                if ($routeFile === FileInterpreterError::FileNotSupported) {
                    // For now, just ignore unsupported file types.
                    // @todo This should probably get logged, at least.
                    continue;
                }

                $toBuild[$routeFile->logicalPath] ??= [
                    'type' => 'page',
                    'variants' => [],
                    'order' => $order,
                    'fileName' => pathinfo($routeFile->logicalPath, PATHINFO_FILENAME),
                ];

                $toBuild[$routeFile->logicalPath]['variants'][$file->getExtension()] = $routeFile;

            } else {
                [$basename, $order] = $this->parseName($file->getFilename());

                $physicalPath = $file->getPathname();
                // @todo This gets more flexible.
                $logicalPath = rtrim($this->logicalPath, '/') . '/' . $basename;

                $toBuild[$logicalPath] ??= [
                    'type' => 'folder',
                    'physicalPath' => $physicalPath,
                    'order' => $order,
                    'fileName' => $basename,
                ];

                $toBuild[$logicalPath]['data'] = $file;
            }
        }

        // @todo Figure out how to get the title in here somehow.
        $comparator = static fn (array $a, array $b) => [$a['order']] <=> [$b['order']];
        uasort($toBuild, $comparator);

        $children = [];
        foreach ($toBuild as $logicalPath => $child) {
            if ($child['type'] === 'folder') {
                $children[$child['fileName']] = new FolderRef($child['physicalPath'], $logicalPath);
            } else {
                $children[$child['fileName']] = new Page($logicalPath, $child['variants']);
            }
        }

        return new FolderData($this->physicalPath, $this->logicalPath, $children);
    }

    protected function parseName(string $basename): array
    {
        preg_match(self::StripPrefix, $basename, $matches);

        $order = 0;
        if (array_key_exists(1, $matches)) {
            $order = str_replace(['_', '-'], '', ltrim($matches[1], '0')) ?: 0;
            $basename = $matches[2];
        }

        return [$basename, $order];
    }
}
