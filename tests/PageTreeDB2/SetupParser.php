<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\PageTreeDB2\Parser\LatteFileParser;
use Crell\MiDy\PageTreeDB2\Parser\MarkdownLatteFileParser;
use Crell\MiDy\PageTreeDB2\Parser\MultiplexedFileParser;
use Crell\MiDy\PageTreeDB2\Parser\Parser;
use Crell\MiDy\PageTreeDB2\Parser\PhpFileParser;
use Crell\MiDy\PageTreeDB2\Parser\StaticFileParser;
use PHPUnit\Framework\Attributes\Before;

trait SetupParser
{
    use SetupCache;

    private Parser $parser;

    #[Before(priority: 5)]
    public function setupParser(): void
    {
        $fileParser = new MultiplexedFileParser();
        $fileParser->addParser(new StaticFileParser(new StaticRoutes()));
        $fileParser->addParser(new PhpFileParser());
        $fileParser->addParser(new LatteFileParser());
        $fileParser->addParser(new MarkdownLatteFileParser(new MarkdownPageLoader()));

        $this->parser = new Parser($this->cache, $fileParser);
    }
}
