<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\TimedCache\TimedCache;

/**
 * Parser for filesystem data.
 *
 * This class takes a folder and determines what its internal logical structure
 * should be.  It also caches it as appropriate.
 */
readonly class FolderParser
{
    private const string StripPrefix = '/^([\d_-]+)_(.*)/m';
    public const string ControlFile = 'folder.midy';

    public function __construct(
        public TimedCache $cache,
        protected FileInterpreter $interpreter,
    ) {}

    /**
     * Loads the logical structure for a given folder.
     */
    public function loadFolder(Folder $folder): FolderData
    {
        $regenerator = fn() => $this->reindex($folder);
        return $this->cache->get($folder->logicalPath, filemtime($folder->physicalPath), $regenerator);
    }

    private function reindex(Folder $folder): FolderData
    {
        $controlData = $this->parseControlFile($folder->physicalPath);

        $datalist = new FolderParserDatalist($controlData['sortOrder']);

        /** @var \SplFileInfo $file */
        foreach ($this->getChildIterator($folder->physicalPath, $controlData['flatten']) as $file) {
            if ($file->isFile()) {
                // SPL is so damned stupid...
                [$basename, $order] = $this->parseName($file->getBasename('.' . $file->getExtension()));
                $routeFile = $this->interpreter->map($file, $folder->logicalPath, $basename);

                $datalist->addRouteFile($file->getExtension(), $basename, $order, $routeFile);
            } else {
                [$basename, $order] = $this->parseName($file->getFilename());
                $childPhysicalPath = $file->getPathname();
                $childLogicalPath = rtrim($folder->logicalPath, '/') . '/' . $basename;
                $childControlData = $this->parseControlFile($childPhysicalPath);

                $datalist->addFolder($basename, $order, $file, $childControlData, $childPhysicalPath, $childLogicalPath);
            }
        }

        $children = [];
        foreach ($datalist as $childLogicalPath => $child) {
            $children[$child['fileName']] = match ($child['type']) {
                'folder' => new FolderRef($child['physicalPath'], $childLogicalPath, $child['hidden']),
                'page' => new Page($childLogicalPath, $child['variants'], $child['hidden']),
            };
        }

        return new FolderData($folder->physicalPath, $folder->logicalPath, $children);
    }

    /**
     * Creates an iterator for the specified path and configuration.
     *
     * @return iterable<\SplFileInfo>
     */
    private function getChildIterator(string $physicalPath, bool $flatten): iterable
    {
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
        $iter = new \CallbackFilterIterator($iter, static fn(\SplFileInfo $f) => $f->getBasename() !== self::ControlFile);

        return $iter;
    }

    /**
     * Parse the control file for a directory, which tells us how to handle it.
     *
     * @todo using an array here is gross.
     * @return array{'sortOrder': SortOrder, 'flatten': bool, 'hidden': bool}
     */
    private function parseControlFile(string $physicalPath): array
    {
        $ret = [
            'sortOrder' => SortOrder::Asc,
            'flatten' => false,
            'hidden' => false,
        ];

        $controlFile = $physicalPath . '/' . self::ControlFile;
        if (!file_exists($controlFile)) {
            return $ret;
        }

        // @todo We can probably do better than this manual nonsense, but I'd prefer to not
        //   inject Serde into the Folder tree as well.
        $contents = file_get_contents($controlFile);
        try {
            $def = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            $ret['sortOrder'] = SortOrder::fromString($def['order'] ?? null) ?? SortOrder::Asc;
            $ret['flatten'] = $def['flatten'] ?? false;
            $ret['hidden'] = $def['hidden'] ?? false;
        } catch (\JsonException) {
            // @todo Log this, but otherwise we don't care.
        }

        return $ret;
    }

    /**
     * Parses the ordering value out of a file basename.
     *
     * @return array{string, int}
     *     The de-prefixed basename, and the ordering value as an integer.
     */
    protected function parseName(string $basename): array
    {
        preg_match(self::StripPrefix, $basename, $matches);

        $order = 0;
        if (array_key_exists(1, $matches)) {
            $order = str_replace(['_', '-'], '', ltrim($matches[1], '0')) ?: 0;
            $basename = $matches[2];
        }

        return [$basename, (int)$order];
    }
}
