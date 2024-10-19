<?php

declare(strict_types=1);

namespace Crell\MiDy\Listeners;

use Crell\MiDy\ActionRunner;
use Crell\MiDy\Config\General;
use Crell\MiDy\Errors\NotFound;
use Crell\MiDy\Events\HandleError;
use Crell\MiDy\Router\Router;
use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\Router\RouteSuccess;
use Crell\Tukio\Listener;
use Psr\Http\Message\RequestFactoryInterface;

#[Listener]
readonly class NotFoundError
{
    public function __construct(
        private General $config,
        private Router $router,
        RequestFactoryInterface $requestFactory,
        private ActionRunner $runner,
    ) {}

    public function __invoke(HandleError $event): void
    {
        if ($event->error instanceof NotFound) {
            $errorPath = $this->config->specialFilesPath . '/404';
            $request = $event->request
                ->withMethod('GET')
                ->withParsedBody(null)
                ->withUri($event->request->getUri()->withPath($errorPath))
            ;
            $routeResult = $this->router->route($request);

            if ($routeResult instanceof RouteSuccess) {
                $request = $request->withAttribute(RouteResult::class, $routeResult);
                $response = $this->runner->handle($request)
                    ->withStatus(404);
                $event->setResponse($response);
            }
        }
    }
}
