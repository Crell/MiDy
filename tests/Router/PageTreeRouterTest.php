<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\MiDy;
use Crell\MiDy\PageTree\Page;
use Crell\MiDy\PageTree\PageTree;
use Crell\MiDy\PageTree\Parser\LatteFileParser;
use Crell\MiDy\PageTree\Parser\MarkdownLatteFileParser;
use Crell\MiDy\PageTree\Parser\MultiplexedFileParser;
use Crell\MiDy\PageTree\Parser\Parser;
use Crell\MiDy\PageTree\Parser\PhpFileParser;
use Crell\MiDy\PageTree\Parser\StaticFileParser;
use Crell\MiDy\PageTree\SetupDB;
use Crell\MiDy\PageTree\SetupRepo;
use Crell\MiDy\Router\PageTreeRouter\PageHandler;
use Crell\MiDy\Router\PageTreeRouter\PageTreeRouter;
use Crell\MiDy\Router\PageTreeRouter\SupportsTrailingPath;
use Crell\MiDy\SetupFilesystem;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class PageTreeRouterTest extends TestCase
{
    use SetupFilesystem;
    use SetupDB;
    use SetupRepo;

    private Midy $app;
    private Parser $parser;
    protected PageTree $pageTree;

    public function setUp(): void
    {
        $this->app = new MiDy('.');
    }

    #[Before(priority: 10)]
    public function setupParser(): void
    {
        $fileParser = new MultiplexedFileParser();
        $fileParser->addParser(new StaticFileParser(new StaticRoutes()));
        $fileParser->addParser(new PhpFileParser());
        $fileParser->addParser(new LatteFileParser());
        $fileParser->addParser(new MarkdownLatteFileParser(new MarkdownPageLoader()));

        $this->parser = new Parser($this->repo, $fileParser);
    }

    protected function makeRequest(string $path, string $method = 'GET'): ServerRequestInterface
    {
        /** @var ServerRequestCreatorInterface $creator */
        $creator = $this->app->container->get(ServerRequestCreator::class);

        return $creator->fromArrays(
            server: [
                'REQUEST_URI' => $path,
                'REQUEST_METHOD' => $method,
            ],
            headers: [
                'Accept' => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/png,image/svg+xml,*/*;q=0.8",
            ],
        );
    }

    #[Before(priority: 5)]
    protected function makePageTree(): void
    {
        $this->pageTree = new PageTree($this->repo, $this->parser, $this->routesPath);
    }

    #[Test, RunInSeparateProcess]
    public function basic(): void
    {
        mkdir($this->routesPath . '/foo');
        file_put_contents($this->routesPath . '/foo/bar.md', '# Title here');

        $router = new PageTreeRouter($this->pageTree);

        $router->addHandler(new class implements PageHandler {
            public array $supportedMethods = ['GET'];
            public array $supportedExtensions = ['md'];

            public function handle(ServerRequestInterface $request, Page $page, string $ext): ?RouteResult
            {
                return new RouteSuccess('action', 'GET', vars: ['a' => 1, 'b' => 2]);
            }
        });

        $request = $this->makeRequest('/foo/bar')
            ->withAttribute(RequestPath::class, new RequestPath('/foo/bar'))
            ->withAttribute(RequestFormat::class, new RequestFormat('text/html', 'text/html'));

        $result = $router->route($request);

        self::assertInstanceOf(RouteSuccess::class, $result);
        self::assertEquals('action', $result->action);
        self::assertEquals('GET', $result->method);
    }

    #[Test, RunInSeparateProcess]
    public function trailing_path_skips_non_trailing_handlers(): void
    {
        mkdir($this->routesPath . '/afolder');
        mkdir($this->routesPath . '/afolder/sub1');
        mkdir($this->routesPath . '/afolder/sub1/sub2');
        mkdir($this->routesPath . '/afolder/sub1/sub2/sub3');
        file_put_contents($this->routesPath . '/afolder/sub1/test.md', '# Will not use');

        $router = new PageTreeRouter($this->pageTree);

        $router->addHandler(new class implements PageHandler {
            public array $supportedMethods = ['GET'];
            public array $supportedExtensions = ['md'];

            public function handle(ServerRequestInterface $request, Page $page, string $ext): ?RouteResult
            {
                return new RouteSuccess('action', 'GET', vars: ['a' => 1, 'b' => 2]);
            }
        });

        $request = $this->makeRequest('/afolder/sub1/sub2/sub3')
            ->withAttribute(RequestPath::class, new RequestPath('/afolder/sub1/sub2/sub3'))
            ->withAttribute(RequestFormat::class, new RequestFormat('text/html', 'text/html'));

        $result = $router->route($request);

        self::assertInstanceOf(RouteNotFound::class, $result);
    }

    #[Test, RunInSeparateProcess]
    public function trailing_path_works_on_trailing_handler(): void
    {
        mkdir($this->routesPath . '/afolder2');
        mkdir($this->routesPath . '/afolder2/sub1');
        file_put_contents($this->routesPath . '/afolder2/sub1/test.md', '# Will not use');

        $router = new PageTreeRouter($this->pageTree);

        $router->addHandler(new class implements SupportsTrailingPath {
            public array $supportedMethods = ['GET'];
            public array $supportedExtensions = ['md'];

            public function handle(ServerRequestInterface $request, Page $page, string $ext, array $trailing = []): ?RouteResult
            {
                return new RouteSuccess('action', 'GET', vars: ['trailing' => $trailing]);
            }
        });

        $request = $this->makeRequest('/afolder2/sub1/test/extra/here')
            ->withAttribute(RequestPath::class, new RequestPath('/afolder2/sub1/test/extra/here'))
            ->withAttribute(RequestFormat::class, new RequestFormat('text/html', 'text/html'));

        $result = $router->route($request);

        self::assertInstanceOf(RouteSuccess::class, $result);
        self::assertEquals('action', $result->action);
        self::assertEquals('GET', $result->method);
        self::assertEquals(['trailing' => ['extra', 'here']], $result->vars);
    }
}
