<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\PageTree\Parser\HtmlFileParser;
use Crell\MiDy\PageTree\Parser\LatteFileParser;
use Crell\MiDy\PageTree\Parser\MarkdownLatteFileParser;
use Crell\MiDy\PageTree\Parser\MultiplexedFileParser;
use Crell\MiDy\PageTree\Parser\Parser;
use Crell\MiDy\PageTree\Parser\PhpFileParser;
use Crell\MiDy\PageTree\Parser\StaticFileParser;
use PHPUnit\Framework\Attributes\Before;

trait SetupParser
{
    use SetupRepo;

    private Parser $parser;

    #[Before(5)]
    public function setupParser(): void
    {
        $fileParser = new MultiplexedFileParser();
        $fileParser->addParser(new HtmlFileParser());
        $fileParser->addParser(new StaticFileParser(new StaticRoutes()));
        $fileParser->addParser(new PhpFileParser());
        $fileParser->addParser(new LatteFileParser());
        $fileParser->addParser(new MarkdownLatteFileParser(new MarkdownPageLoader()));

        $this->parser = new Parser($this->repo, $fileParser);
    }
}
