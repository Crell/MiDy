<?php

declare(strict_types=1);

namespace Crell\MiDy;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\TimedCache\FilesystemTimedCache;
use Crell\MiDy\Tree\FileInterpreter;
use Crell\MiDy\Tree\FolderParser;
use Crell\MiDy\Tree\LatteFileInterpreter;
use Crell\MiDy\Tree\MarkdownLatteFileInterpreter;
use Crell\MiDy\Tree\MultiplexedFileInterpreter;
use Crell\MiDy\Tree\PhpFileInterpreter;
use Crell\MiDy\Tree\RootFolder;
use Crell\MiDy\Tree\StaticFileInterpreter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\BeforeClass;

trait RootFilesystemSetup
{
    use FakeFilesystem;

    /**
     * The VFS needs to be static so it's reused, so the require_once() call in the PHP interpreter
     * can not require the "same" file multiple times, leading to double-declaration errors.
     */
    protected static vfsStreamDirectory $vfs;

    #[BeforeClass]
    public static function initFilesystem(): vfsStreamDirectory
    {
        // This mess is because vfsstream doesn't let you create multiple streams
        // at the same time.  Which is dumb.
        $structure = [
            'cache' => [],
            'data' => self::simpleStructure(),
        ];

        return self::$vfs = vfsStream::setup('root', null, $structure);
    }

    protected function makeRootFolder(): RootFolder
    {
        $filePath = self::$vfs->getChild('data')->url();
        $cachePath = self::$vfs->getChild('cache')->url();

        $cache = new FilesystemTimedCache($cachePath);

        $cache->clear();

        $parser = new FolderParser($cache, $this->makeFileInterpreter());

        return new RootFolder($filePath, $parser);
    }

    protected function makeFileInterpreter(): FileInterpreter
    {
        $i = new MultiplexedFileInterpreter();
        $i->addInterpreter(new StaticFileInterpreter(new StaticRoutes()));
        $i->addInterpreter(new PhpFileInterpreter(new ClassFinder()));
        $i->addInterpreter(new LatteFileInterpreter());
        $i->addInterpreter(new MarkdownLatteFileInterpreter(new MarkdownPageLoader()));

        return $i;
    }
}