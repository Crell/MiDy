<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\PageTree\Parser\Parser;

trait MakerUtils
{
    private static function makePageRecord(LogicalPath|string $logicalPath, mixed ...$args): PageRecord
    {
        $logicalPath = LogicalPath::create($logicalPath);

        $defaults = [
            'logicalPath' => $logicalPath,
            'folder' => (string) $logicalPath->parent(),
            'title' => $logicalPath->end,
            'summary' => '',
            'order' => 0,
            'hidden' => false,
            'routable' => true,
            'isFolder' => false,
            'publishDate' => new \DateTimeImmutable('2024-10-31'),
            'lastModifiedDate' => new \DateTimeImmutable('2024-10-31'),
            'tags' => [],
            'files' => [],
        ];

        $args += $defaults;

        return new PageRecord(...$args);
    }

    private static function makeParsedFolder(PhysicalPath|string $physicalPath, mixed ...$args): ParsedFolder
    {
        $args['physicalPath'] = PhysicalPath::create($physicalPath);

        $defaults = [
            'physicalPath' => $args['physicalPath'],
            'logicalPath' => LogicalPath::fromPhysicalPath($args['physicalPath']),
            'mtime' => 0,
            'flatten' => false,
            'title' => $args['physicalPath']->end,
        ];

        $args += $defaults;

        return new ParsedFolder(...$args);
    }

    private static function makeParsedFile(PhysicalPath|string $physicalPath, mixed ...$args): ParsedFile
    {
        $args['physicalPath'] = PhysicalPath::create($physicalPath);

        $defaults = [
            'logicalPath' => LogicalPath::fromPhysicalPath($args['physicalPath']),
            'ext' =>  $args['physicalPath']->ext,
            'physicalPath' =>  PhysicalPath::create('/foo/bar.md'),
            'mtime' =>  123456,
            'title' =>  $args['physicalPath']->end,
            'folder' =>  LogicalPath::fromPhysicalPath($args['physicalPath']->parent()),
            'order' =>  0,
            'hidden' =>  false,
            'routable' =>  true,
            'publishDate' =>  new \DateTimeImmutable('2024-10-31'),
            'lastModifiedDate' =>  new \DateTimeImmutable('2024-10-31'),
            'other' =>  [],
            'tags' => [],
            'summary' =>  '',
            'slug' => null,
            'pathName' =>  $args['physicalPath']->end,
            'isFolder' => false,
        ];

        $args += $defaults;

        // Cloned from Parser::parseFile();
        if ($args['logicalPath']->end === Parser::IndexPageName) {
            // The logical path of the index page is its parent folder's path.
            $args['logicalPath'] = $args['logicalPath']->parent();
            // The folder it should appear under is its folder's parent,
            // so that it "is" a child of that parent.
            $args['folder'] = $args['folder']->parent();
            // The pathName of the index page should be its folder's basename.
            $args['pathName'] = $args['physicalPath']->parent()->end;
            // And flag it as a file representing a folder.
            $args['isFolder'] = true;
        }

        return new ParsedFile(...$args);
    }
}
