<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

use Crell\MiDy\MiDy;
use Crell\MiDy\Router\HandlerRouter\HandlerRouter;
use Crell\MiDy\Router\HandlerRouter\PageHandler;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class HandleRouterTest extends TestCase
{
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
    public function test(): void
    {
        $router = new HandlerRouter('/app/routes');

        $router->addHandler(new class implements PageHandler {
            public function supportedMethods(): array
            {
                return ['GET'];
            }

            public function supportedExtensions(): array
            {
                return ['md'];
            }

            public function handle(ServerRequestInterface $request, string $file, string $ext): ?RouteResult
            {
                return new RouteSuccess('action', 'GET', vars: ['a' => 1, 'b' => 2]);
            }
        });

        $request = $this->makeRequest('/subdir/page-three')
            ->withAttribute(RequestPath::class, new RequestPath('/subdir/page-three'))
            ->withAttribute(RequestFormat::class, new RequestFormat('text/html', 'text/html'));

        $result = $router->route($request);

        self::assertInstanceOf(RouteSuccess::class, $result);
        self::assertEquals('action', $result->action);
        self::assertEquals('GET', $result->method);
    }
}
