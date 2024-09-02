<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

use Crell\MiDy\MiDy;
use Crell\MiDy\PageHandlers\SupportsTrailingPath;
use Crell\MiDy\RootFilesystemSetup;
use Crell\MiDy\Router\HandlerRouter\HandlerRouter;
use Crell\MiDy\Router\HandlerRouter\PageHandler;
use Crell\MiDy\Tree\Page;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class HandlerRouterTest extends TestCase
{
    use RootFilesystemSetup;

    private Midy $app;

    public function setUp(): void
    {
        $this->app = new MiDy('.');
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

    #[Test]
    public function basic(): void
    {
        mkdir('vfs://root/data/foo');
        file_put_contents('vfs://root/data/foo/bar.md', '# Will not use');

        $root = $this->makeRootFolder();

        $router = new HandlerRouter($root);

        $router->addHandler(new class implements PageHandler {
            public function supportedMethods(): array
            {
                return ['GET'];
            }

            public function supportedExtensions(): array
            {
                return ['md'];
            }

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

    #[Test]
    public function trailing_path_skips_non_trailing_handlers(): void
    {
        mkdir('vfs://root/data/afolder');
        mkdir('vfs://root/data/afolder/sub1');
        mkdir('vfs://root/data/afolder/sub1/sub2');
        mkdir('vfs://root/data/afolder/sub1/sub2/sub3');
        file_put_contents('vfs://root/data/afolder/sub1/test.md', '# Will not use');

        $root = $this->makeRootFolder();

        $router = new HandlerRouter($root);

        $router->addHandler(new class implements PageHandler {
            public function supportedMethods(): array { return ['GET']; }

            public function supportedExtensions(): array { return ['md']; }

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

    #[Test]
    public function trailing_path_works_on_trailing_handler(): void
    {
        mkdir('vfs://root/data/afolder2');
        mkdir('vfs://root/data/afolder2/sub1');
        file_put_contents('vfs://root/data/afolder2/sub1/test.md', '# Will not use');

        $root = $this->makeRootFolder();

        $router = new HandlerRouter($root);

        $router->addHandler(new class implements SupportsTrailingPath {
            public function supportedMethods(): array { return ['GET']; }

            public function supportedExtensions(): array { return ['md']; }

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
