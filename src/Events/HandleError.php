<?php

declare(strict_types=1);

namespace Crell\MiDy\Events;

use Crell\MiDy\Errors\Error;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Http\Message\ServerRequestInterface;

class HandleError implements StoppableEventInterface, CarriesResponse
{
    use ResponseCarrier;

    public function __construct(
        public readonly Error $error,
        public readonly ServerRequestInterface $request,
    ) {}

    public function isPropagationStopped(): bool
    {
        return isset($this->response);
    }
}
