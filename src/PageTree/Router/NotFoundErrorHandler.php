<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Router;

use Crell\Carica\ResponseBuilder;
use Crell\Carica\Router\ActionDispatcher;
use Crell\Carica\Router\RouteResult;
use Crell\MiDy\Config\General;
use Crell\Carica\Router\Router;
use Crell\Carica\Router\RouteSuccess;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class NotFoundErrorHandler implements RequestHandlerInterface
{
    /**
     * I don't like the container here, but we need to access to the router, which creates a circular dependency otherwise.
     */
    public function __construct(
        private General $config,
        private ContainerInterface $container,
        private ActionDispatcher $runner,
        private ResponseBuilder $responseBuilder,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $errorPath = '/' . trim($this->config->specialFilesPath, '/') . '/404';
        $request = $request
            ->withMethod('GET')
            ->withParsedBody(null)
            ->withUri($request->getUri()->withPath($errorPath))
        ;
        $routeResult = $this->container->get(Router::class)->route($request);

        if ($routeResult instanceof RouteSuccess) {
            $request = $request->withAttribute(RouteResult::class, $routeResult);
            return $this->runner->handle($request)
                ->withStatus(404);
        }

        return $this->responseBuilder->notFound('File not found.');
    }
}