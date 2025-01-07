<?php

declare(strict_types=1);

namespace Crell\MiDy;

use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class HttpValidationTest extends TestCase
{
    private Midy $app;

    public function setUp(): void
    {
        $root = vfsStream::setup('root', structure: [
            'routes' => [],
            'cache' => [
                'routes' => [],
                'latte' => [],
                'config' => [],
            ],
        ]);

        vfsStream::copyFromFileSystem(__DIR__ . '/test-routes', $root->getChild('routes'));

        $this->app = new MiDy('.',
            routesPath: $root->getChild('routes')->url(),
        );
    }

    #[After]
    public function clearCaches(): void
    {
        $this->clearDirectory('./cache/routes');
        $this->clearDirectory('./cache/latte');
        $this->clearDirectory('./cache/config');
    }

    protected function clearDirectory(string $path): void
    {
        $files = glob($path . '/*');

        foreach($files as $file) {
            if(is_file($file)) {
                unlink($file);
            }
        }
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

    public static function successGetRoutes(): iterable
    {
        return [
            'index' => ['/'],
            'static html' => ['/html-test'],
            'latte page' => ['/latte-test'],
            'markdown page' => ['/md-test'],
            'png image' => ['/png-test'],
            'php handler' => ['/php-test'],
            'png image with extension' => ['/png-test.png'],
            'subdir page' => ['/subdir/page-three'],
            'subdir index' => ['/subdir'],
        ];
    }

    #[Test, DataProvider('successGetRoutes'), RunInSeparateProcess]
    public function basic_200_checks(string $path): void
    {
        $serverRequest = $this->makeRequest($path);

        $response = $this->app->handle($serverRequest);

        self::assertEquals(200, $response->getStatusCode());
    }

    #[Test, RunInSeparateProcess]
    public function not_found_handling(): void
    {
        $serverRequest = $this->makeRequest('/missing');

        $response = $this->app->handle($serverRequest);

        self::assertEquals(404, $response->getStatusCode());
        self::assertStringContainsString('We apologize for the inconvenience.', $response->getBody()->getContents());
        self::assertEmpty($response->getHeaderLine('cache-control'));
    }

    #[Test, RunInSeparateProcess]
    public function tree_ordering(): void
    {
        $app = $this->app;

        $serverRequest = $this->makeRequest('/');

        $response = $app->handle($serverRequest);

        self::assertEquals(200, $response->getStatusCode());

        $body = $response->getBody()->getContents();
        $zpos = strpos($body, 'ordered/z');
        $kpos = strpos($body, 'ordered/k');
        $jpos = strpos($body, 'ordered/j');

        self::assertNotFalse($zpos);
        self::assertNotFalse($kpos);
        self::assertNotFalse($jpos);
        self::assertTrue($zpos < $kpos);
        self::assertTrue($kpos < $jpos);
    }

    #[Test, RunInSeparateProcess]
    public function tree_ordering_reversed(): void
    {
        $app = $this->app;

        $serverRequest = $this->makeRequest('/');

        $response = $app->handle($serverRequest);

        self::assertEquals(200, $response->getStatusCode());

        $body = $response->getBody()->getContents();
        $zpos = strpos($body, 'reversed/z');
        $kpos = strpos($body, 'reversed/k');
        $jpos = strpos($body, 'reversed/j');

        self::assertNotFalse($zpos);
        self::assertNotFalse($kpos);
        self::assertNotFalse($jpos);
        self::assertTrue($jpos < $kpos);
        self::assertTrue($kpos < $zpos);
    }

    // @TODO Tests for static file cache headers, and then for all other file types.
    //   Also make sure 404/403 pages are not cached.


    #[Test, DataProvider('successGetRoutes'), RunInSeparateProcess]
    public function cache_headers_etag(string $path): void
    {
        $serverRequest = $this->makeRequest($path);

        $response = $this->app->handle($serverRequest);

        self::assertEquals(200, $response->getStatusCode());

        $modifiedTimeRequest = $serverRequest
            ->withHeader('if-modified-since', $response->getHeaderLine('last-modified'))
        ;
        $response = $this->app->handle($modifiedTimeRequest);
        self::assertEquals(304, $response->getStatusCode());

        $etagRequest = $serverRequest
            ->withHeader('if-none-match', $response->getHeaderLine('etag'))
        ;
        $response = $this->app->handle($modifiedTimeRequest);
        self::assertEquals(304, $response->getStatusCode());
    }
}
