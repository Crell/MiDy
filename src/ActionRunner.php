<?php

declare(strict_types=1);

namespace Crell\MiDy;

use Crell\MiDy\Events\Events\ProcessActionResult;
use Crell\MiDy\Services\ActionInvoker;
use Crell\MiDy\Services\ResponseBuilder;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

readonly class ActionRunner implements RequestHandlerInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private ResponseBuilder $responseBuilder,
        private ActionInvoker $invoker,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $result = $this->invoker->invokeAction($request);

        if ($result instanceof ResponseInterface) {
            return $result;
        }

        /** @var ProcessActionResult $event */
        $event = $this->eventDispatcher->dispatch(new ProcessActionResult($result, $request));

        return $event->getResponse() ?? $this->responseBuilder->createResponse(500, 'No matching result processor found', 'text/plain');
    }
}
