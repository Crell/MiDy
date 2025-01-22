<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2\Parser;

use Crell\MiDy\PageTree\FolderDef;
use Crell\MiDy\PageTreeDB2\PageCacheDB;
use Crell\MiDy\PageTreeDB2\PageRepo;
use Crell\MiDy\PageTreeDB2\ParsedFile;
use Crell\MiDy\PageTreeDB2\ParsedFolder;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;

use function Crell\fp\amap;
use function Crell\fp\pipe;

class Parser
{
    private const string StripPrefix = '/^([\d_-]+)_(.*)/m';
    public const string ControlFile = 'folder.midy';
    public const string IndexPageName = 'index';

    public function __construct(
        private readonly PageRepo $cache,
        private readonly FileParser $fileParser,
        private readonly Serde $serde = new SerdeCommon(),
    ) {}

    public function parseFolder(string $physicalPath, string $logicalPath, array $mounts): bool
    {
        return $this->cache->inTransaction(function() use ($physicalPath, $logicalPath, $mounts) {
            $controlData = $this->parseControlFile($physicalPath);

            if (!file_exists($physicalPath)) {
                return false;
            }

            // Rebuild the folder record.
            $this->cache->deleteFolder($logicalPath);
            $folderInfo = new \SplFileInfo($physicalPath);
            $folder = new ParsedFolder(
                logicalPath: $logicalPath,
                physicalPath: $physicalPath,
                mtime: $folderInfo->getMTime(),
                flatten: $controlData->flatten,
                title: $folderInfo->getBasename(),
            );
            $this->cache->writeFolder($folder);

            $children = new ParserFileList($controlData->order);

            // Now reindex every file in the folder.
            /** @var \SplFileInfo $file */
            foreach ($this->getChildIterator($physicalPath, $controlData->flatten) as $file) {
                if ($file->isFile()) {
                    $children->addParsedFile($this->parseFile($file, $logicalPath));
                } else {
                    // It's a directory.
                    [$basename, $order] = $this->parseName($file->getFilename());
                    $childPhysicalPath = $file->getPathname();

                    // I really dislike needing to pass the mounts list in here,
                    // but I don't know of another way to be able to get a logical
                    // name for the folder that doesn't match the physical path,
                    // when the folder is a mount point.
                    if ($key = array_find_key($mounts, static fn(string $val, string $key) => $val === $physicalPath . '/' . $basename)) {
                        $basename = trim($key, '/');
                    }

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

                    // See if the folder has an index page, in which case we treat that
                    // as the "folder".
                    $childIndexFile = $this->getIndexFile($childFolder->physicalPath);
                    if ($childIndexFile !== null) {
                        $children->addParsedFile($this->parseFile($childIndexFile, $childFolder->logicalPath, $order));
                    }
                }
            }

            // Now write out all the children.
            amap($this->cache->writePage(...))($children);

            // The folder was parsed successfully.
            return true;
        });
    }

    public function parseFile(\SplFileInfo $file, string $folderLogicalPath, ?int $orderOverride = null): ?ParsedFile
    {
        // SPL is so damned stupid...
        [$basename, $order] = $this->parseName($file->getBasename('.' . $file->getExtension()));
        $parsedFile = $this->fileParser->map($file, $folderLogicalPath, $basename);
        if ($parsedFile instanceof FileParserError) {
            // @todo Log or something?
            return null;
        }
        $parsedFile->order = $orderOverride ?? $order;
        // In case it's an index page, we need to "shift up" some of the data
        // since the file is standing in for its folder.
        if ($basename === self::IndexPageName) {
            // The logical path of the index page is its parent folder's path.
            $parsedFile->logicalPath = dirname($parsedFile->logicalPath);
            // The folder it should appear under is its folder's parent,
            // so that it "is" a child of that parent.
            $parsedFile->folder = dirname($parsedFile->folder);
            // The pathName of the index page should be its folder's basename.
            $folderParts = \explode('/', $folderLogicalPath);
            $parsedFile->pathName = array_pop($folderParts);
            // And flag it as a file representing a folder.
            $parsedFile->isFolder = true;
        }

        return $parsedFile;
    }

    private function getIndexFile(string $folderPhysicalPath): ?\SplFileInfo
    {
        $indexFilter = static fn(\SplFileInfo $f) => $f->getBasename('.' . $f->getExtension()) === self::IndexPageName;
        $iter = new \CallbackFilterIterator($this->getChildIterator($folderPhysicalPath, false), $indexFilter);
        $files = iterator_to_array($iter);
        return current($files) ?: null;   // @todo More robust than guessing it's the first file.
    }

    /**
     * Creates an iterator for the specified path and configuration.
     *
     * @return iterable<\SplFileInfo>
     */
    private function getChildIterator(string $physicalPath, bool $flatten): \Iterator
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
