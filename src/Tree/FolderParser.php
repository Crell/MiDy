<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

use Crell\MiDy\TimedCache\TimedCache;

readonly class FolderParser
{
    private const string StripPrefix = '/^([\d_-]+)_(.*)/m';

    public function __construct(
        public TimedCache $cache,
        protected FileInterpreter $interpreter,
    ) {}

    public function loadFolder(Folder $folder): FolderData
    {
        $regenerator = fn() => $this->reindex($folder->physicalPath, $folder->logicalPath);
        return $this->cache->get($folder->logicalPath, filemtime($folder->physicalPath), $regenerator);
    }

    private function reindex(string $physicalPath, string $logicalPath): FolderData
    {
        $toBuild = [];
        $sortOrder = SortOrder::Asc;
        $flatten = false;

        // The control file provides extra metadata to the folder itself.
        $controlFile = $physicalPath . '/' . Folder::ControlFile;
        if (file_exists($controlFile)) {
            // @todo We can probably do better than this manual nonsense, but I'd prefer to not
            //   inject Serde into the Folder tree as well.
            $contents = file_get_contents($controlFile);
            try {
                $def = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
                $sortOrder = SortOrder::fromString($def['order'] ?? null) ?? SortOrder::Asc;
                $flatten = $def['flatten'] ?? false;
            } catch (\JsonException) {
                // @todo Log this, but otherwise we don't care.
            }
        }

        // @todo This approach has one limitation: The order of the skipped directories has no effect.
        //   If the files themselves have a logical ordering, that's no issue. If not, that could be
        //   unexpected.  I'm not sure how to address that other than doing all the recursion manually
        //   in an entirely separate routine, so I'm skipping that for now.
        $flags = \FilesystemIterator::KEY_AS_PATHNAME|\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS;
        if ($flatten) {
            $filter = static fn(\SplFileInfo $f) => $f->isFile();
            $iter = new \CallbackFilterIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($physicalPath, flags: $flags)), $filter);
        } else {
            $iter = new \FilesystemIterator($physicalPath, flags: $flags);
        }

        // Never show the control file.
        $iter = new \CallbackFilterIterator($iter, static fn(\SplFileInfo $f) => $f->getBasename() !== Folder::ControlFile);

        /** @var \SplFileInfo $file */
        foreach ($iter as $file) {
            if ($file->isFile()) {
                // SPL is so damned stupid...
                [$basename, $order] = $this->parseName($file->getBasename('.' . $file->getExtension()));

                $routeFile = $this->interpreter->map($file, $logicalPath, $basename);

                if ($routeFile === FileInterpreterError::FileNotSupported) {
                    // For now, just ignore unsupported file types.
                    // @todo This should probably get logged, at least.
                    continue;
                }

                $toBuild[$routeFile->logicalPath] ??= [
                    'type' => 'page',
                    'variants' => [],
                    'order' => $order,
                    'hidden' => false,
                    'fileName' => pathinfo($routeFile->logicalPath, PATHINFO_FILENAME),
                ];

                if ($basename === Folder::IndexPageName) {
                    $toBuild[$routeFile->logicalPath]['hidden'] = true;
                }

                $toBuild[$routeFile->logicalPath]['variants'][$file->getExtension()] = $routeFile;
            } else {
                [$basename, $order] = $this->parseName($file->getFilename());

                $childPhysicalPath = $file->getPathname();
                $childLogicalPath = rtrim($logicalPath, '/') . '/' . $basename;
                $hidden = false;

                // @todo The existence of this mess means we definitely need to move
                //   the control file handling to a separate method/typedef.
                $childControlFile = $childPhysicalPath . '/' . Folder::ControlFile;
                if (file_exists($childControlFile)) {
                    $contents = file_get_contents($childControlFile);
                    try {
                        $def = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
                        $hidden = $def['hidden'] ?? false;
                    } catch (\JsonException) {
                        // @todo Log this, but otherwise we don't care.
                    }
                }

                $toBuild[$childLogicalPath] ??= [
                    'type' => 'folder',
                    'physicalPath' => $childPhysicalPath,
                    'order' => $order,
                    'fileName' => $basename,
                    'hidden' => $hidden,
                ];

                $toBuild[$childLogicalPath]['data'] = $file;
            }
        }

        // @todo Figure out how to get the title in here somehow.
        $comparator = match ($sortOrder) {
            SortOrder::Asc => static fn (array $a, array $b) => [$a['order']] <=> [$b['order']],
            SortOrder::Desc => static fn (array $a, array $b) => [$b['order']] <=> [$a['order']],
        };
        uasort($toBuild, $comparator);

        $children = [];
        foreach ($toBuild as $childLogicalPath => $child) {
            if ($child['type'] === 'folder') {
                $children[$child['fileName']] = new FolderRef($child['physicalPath'], $childLogicalPath, $child['hidden']);
            } else {
                $children[$child['fileName']] = new Page($childLogicalPath, $child['variants'], $child['hidden']);
            }
        }

        return new FolderData($physicalPath, $logicalPath, $children);
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
