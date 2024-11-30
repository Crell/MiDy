<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\PageTree\FolderDef;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;

class Parser
{
    private const string StripPrefix = '/^([\d_-]+)_(.*)/m';
    public const string ControlFile = 'folder.midy';

//    private array $mounts = [];

    public function __construct(
//        string $rootPhysicalPath,
        private PageCacheDB $cache,
        private FileParser $fileParser,
        private Serde $serde = new SerdeCommon(),
    ) {
//        $this->mounts['/'] = $rootPhysicalPath;
    }

//    public function mountFolder(string $physicalPath, string $logicalPath)
//    {
//        $this->mounts[$logicalPath] = $physicalPath;
//    }

    public function parseFolder(string $physicalPath, string $logicalPath)
    {
        $controlData = $this->parseControlFile($physicalPath);

        $this->cache->deleteFolder($logicalPath);

        /** @var \SplFileInfo $file */
        foreach ($this->getChildIterator($physicalPath, $controlData->flatten) as $file) {
            if ($file->isFile()) {
                // SPL is so damned stupid...
                [$basename, $order] = $this->parseName($file->getBasename('.' . $file->getExtension()));
                $pageFile = $this->fileParser->map($file, $logicalPath, $basename);
                $pageFile->order = $order;

                $this->cache->writeFile($pageFile);
            } else {
                // It's a directory.
                [$basename, $order] = $this->parseName($file->getFilename());
                $childPhysicalPath = $file->getPathname();
                $childLogicalPath = rtrim($logicalPath, '/') . '/' . $basename;
                $childControlData = $this->parseControlFile($childPhysicalPath);

                $childFolder = new ParsedFolder(
                    logicalPath: $childLogicalPath,
                    physicalPath: $childPhysicalPath,
                    mtime: 0,
                    flatten: $childControlData->flatten,
                    title: $file->getBasename(),
                // @todo What do we do with order?  Crap.
                );

                $this->cache->writeFolder($childFolder);
            }
        }

        $folderInfo = new \SplFileInfo($physicalPath);
        $folder = new ParsedFolder(
            logicalPath: $logicalPath,
            physicalPath: $physicalPath,
            mtime: $folderInfo->getMTime(),
            flatten: $controlData->flatten,
            title: $file->getBasename(),
        );

        $this->cache->writeFolder($folder);
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
     */
    private function parseControlFile(string $physicalPath): FolderDef
    {
        $controlFile = $physicalPath . '/' . self::ControlFile;
        if (!file_exists($controlFile)) {
            return new FolderDef();
        }

        $contents = file_get_contents($controlFile);
        return $this->serde->deserialize($contents, from: 'json', to: FolderDef::class);
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
