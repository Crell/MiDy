<?php

declare(strict_types=1);

namespace Crell\MiDy;

use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class HttpValidationTest extends TestCase
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

    public static function successGetRoutes(): iterable
    {
        return [
            ['/html-test'],
            ['/latte-test'],
            ['/md-test'],
            ['/png-test'],
            ['/php-test'],
        ];
    }
    
    #[Test, DataProvider('successGetRoutes')]
    public function basic_200_checks(string $path): void
    {
        $app = $this->app;

        $serverRequest = $this->makeRequest($path);

        $response = $app->handle($serverRequest);

        self::assertEquals(200, $response->getStatusCode());
    }

}