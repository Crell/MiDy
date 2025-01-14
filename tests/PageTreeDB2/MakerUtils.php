<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\PageTree\BasicPageInformation;
use Crell\MiDy\PageTreeDB2\Parser\Parser;

trait MakerUtils
{
    private static function makeParsedFolder(...$args): ParsedFolder
    {
        $parts = pathinfo($args['physicalPath']);

        $defaults = [
            'physicalPath' => $args['physicalPath'],
            'logicalPath' => $args['physicalPath'],
            'mtime' => 0,
            'flatten' => false,
            'title' => $parts['basename'],
        ];

        $args += $defaults;

        return new ParsedFolder(...$args);
    }

    private static function makeParsedFile(...$args): ParsedFile
    {
        $parts = pathinfo($args['physicalPath']);

        $defaults = [
            'logicalPath' => $parts['dirname'] . '/' . $parts['filename'],
            'ext' =>  $parts['extension'],
            'physicalPath' =>  '/foo/bar.md',
            'mtime' =>  123456,
            'title' =>  $parts['filename'],
            'folder' =>  $parts['dirname'],
            'order' =>  0,
            'hidden' =>  false,
            'routable' =>  true,
            'publishDate' =>  new \DateTimeImmutable('2024-10-31'),
            'lastModifiedDate' =>  new \DateTimeImmutable('2024-10-31'),
            'frontmatter' =>  [],
            'summary' =>  '',
            'pathName' =>  $parts['filename'],
        ];

        $args += $defaults;

        $args['frontmatter'] = new BasicPageInformation(...$args['frontmatter']);

        // Cloned from Parser::parseFile();
        if ($parts['filename'] === Parser::IndexPageName) {
            // The logical path of the index page is its parent folder's path.
            $args['logicalPath'] = dirname($args['logicalPath']);
            // The folder it should appear under is its folder's parent,
            // so that it "is" a child of that parent.
            $args['folder'] = dirname($args['folder']);
            // The pathName of the index page should be its folder's basename.
            $folderParts = \explode('/', $parts['dirname']);
            $args['pathName'] = array_pop($folderParts);
            // And flag it as a file representing a folder.
            $args['isFolder'] = true;
        }

        return new ParsedFile(...$args);
    }
}