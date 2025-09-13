<?php

declare(strict_types=1);

namespace Crell\MiDy;

use Crell\MiDy\Events\ProcessActionResult;
use Crell\MiDy\Services\ActionInvoker;
use Crell\Carica\ResponseBuilder;
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
            // Mainly for testing/debugging, but also marketing.
            return $result->withAddedHeader('Generator', 'MiDy');
        }

        /** @var ProcessActionResult $event */
        $event = $this->eventDispatcher->dispatch(new ProcessActionResult($result, $request));

        $response = $event->getResponse() ?? $this->responseBuilder->createResponse(500, 'No matching result processor found', 'text/plain');
        return $response->withAddedHeader('Generator', 'MiDy');
    }
}
