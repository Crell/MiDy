<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Parser;

use Crell\MiDy\PageTree\LogicalPath;
use Crell\MiDy\PageTree\PageCache;
use Crell\MiDy\PageTree\ParsedFile;
use Crell\MiDy\PageTree\ParsedFolder;
use Crell\MiDy\PageTree\PhysicalPath;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;

use function Crell\fp\amap;

class Parser
{
    public const string StripPrefix = '/^([\d_-]+)_(.*)/m';
    public const string ControlFile = 'folder.midy';
    public const string IndexPageName = 'index';

    public function __construct(
        private readonly PageCache $cache,
        private readonly FileParser $fileParser,
        private readonly Serde $serde = new SerdeCommon(),
    ) {}

    /**
     * @param array<string, string> $mounts
     */
    public function parseFolder(PhysicalPath $physicalPath, LogicalPath $logicalPath, array $mounts): bool
    {
        return $this->cache->inTransaction(function() use ($physicalPath, $logicalPath, $mounts) {
            $folderDef = $this->parseControlFile($physicalPath);

            if (!$physicalPath->exists) {
                return false;
            }

            // Rebuild the folder record.
            $this->cache->deleteFolder($logicalPath);
            $folderInfo = $physicalPath->fileInfo;
            $folder = new ParsedFolder(
                logicalPath: $logicalPath,
                physicalPath: $physicalPath,
                mtime: $folderInfo->getMTime(),
                flatten: $folderDef->flatten,
                title: $folderInfo->getBasename(),
            );
            $this->cache->writeFolder($folder);

            $children = new ParserFileList($folderDef->order);

            // Now reindex every file in the folder.
            /** @var \SplFileInfo $file */
            foreach ($this->getChildIterator($physicalPath, $folderDef->flatten) as $file) {
                if ($file->isFile()) {
                    $children->addParsedFile($this->parseFile($file, $logicalPath, $folderDef));
                } else {
                    // It's a directory.
                    [$basename, $order] = $this->parseName($file->getFilename());
                    $childPhysicalPath = PhysicalPath::create($file->getPathname());

                    // I really dislike needing to pass the mounts list in here,
                    // but I don't know of another way to be able to get a logical
                    // name for the folder that doesn't match the physical path,
                    // when the folder is a mount point.
                    if ($key = array_find_key($mounts, static fn(string $val, string $key) => $val === $physicalPath . '/' . $basename)) {
                        $basename = trim($key, '/');
                    }

                    $childLogicalPath = $logicalPath->concat($basename);
                    $childFolderDef = $this->parseControlFile($childPhysicalPath);

                    $childFolder = new ParsedFolder(
                        logicalPath: $childLogicalPath,
                        physicalPath: $childPhysicalPath,
                        mtime: 0,
                        flatten: $childFolderDef->flatten,
                        title: $file->getBasename(),
                    // @todo What do we do with order?  Crap.
                    );
                    $this->cache->writeFolder($childFolder);

                    // See if the folder has an index page, in which case we treat that
                    // as the "folder".
                    $childIndexFile = $this->getIndexFile($childFolder->physicalPath);
                    if ($childIndexFile !== null) {
                        $children->addParsedFile($this->parseFile($childIndexFile, $childFolder->logicalPath, $childFolderDef, $order));
                    }
                }
            }

            // Now write out all the children.
            amap($this->cache->writePage(...))($children);

            // The folder was parsed successfully.
            return true;
        });
    }

    public function parseFile(\SplFileInfo $file, LogicalPath $folderLogicalPath, FolderDef $folderDef = new FolderDef(), ?int $orderOverride = null): ?ParsedFile
    {
        // SPL is so damned stupid...
        [$basename, $order] = $this->parseName($file->getBasename('.' . $file->getExtension()));

        $frontmatter = $this->fileParser->map($file, $folderLogicalPath, $basename);
        if ($frontmatter instanceof FileParserError) {
            // @todo Log or something?
            return null;
        }

        if ($orderOverride) {
            $order = $orderOverride;
        }

        return ParsedFile::createFromParsedData($file, $frontmatter, $folderLogicalPath, $folderDef, $basename, $order);
    }

    private function getIndexFile(PhysicalPath $folderPhysicalPath): ?\SplFileInfo
    {
        $indexFilter = static fn(\SplFileInfo $f) => $f->getBasename('.' . $f->getExtension()) === self::IndexPageName;
        $iter = new \CallbackFilterIterator($this->getChildIterator($folderPhysicalPath, false), $indexFilter);
        $files = iterator_to_array($iter);
        return current($files) ?: null;   // @todo More robust than guessing it's the first file.
    }

    /**
     * Creates an iterator for the specified path and configuration.
     *
     * @return \Iterator<\SplFileInfo>
     */
    private function getChildIterator(PhysicalPath $physicalPath, bool $flatten): \Iterator
    {
        // @todo This approach has one limitation: The order of the skipped directories has no effect.
        //   If the files themselves have a logical ordering, that's no issue. If not, that could be
        //   unexpected.  I'm not sure how to address that other than doing all the recursion manually
        //   in an entirely separate routine, so I'm skipping that for now.
        $flags = \FilesystemIterator::KEY_AS_PATHNAME|\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS;
        if ($flatten) {
            $filter = static fn(\SplFileInfo $f) => $f->isFile();
            $iter = new \CallbackFilterIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator((string)$physicalPath, flags: $flags)
                ), $filter,
            );
        } else {
            $iter = new \FilesystemIterator((string)$physicalPath, flags: $flags);
        }

        // Never show the control file or any .git files.
        $iter = new \CallbackFilterIterator($iter, static fn(\SplFileInfo $f)
            => $f->getBasename() !== self::ControlFile
                && !str_starts_with($f->getBasename(), '.git')
        );

        return $iter;
    }

    /**
     * Parse the control file for a directory, which tells us how to handle it.
     */
    private function parseControlFile(PhysicalPath $physicalPath): FolderDef
    {
        $controlFile = $physicalPath->concat(self::ControlFile);
        if (!$controlFile->exists) {
            return new FolderDef();
        }

        return $this->serde->deserialize($controlFile->contents(), from: 'yaml', to: FolderDef::class);
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
