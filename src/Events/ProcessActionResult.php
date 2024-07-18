<?php

declare(strict_types=1);

namespace Crell\MiDy\Events;

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProcessActionResult implements StoppableEventInterface, CarriesResponse, CarriesError
{
    use ResponseCarrier;
    use ErrorCarrier;

    public function __construct(
        public mixed $result,
        public readonly ServerRequestInterface $request,
    ) {}

    public function isPropagationStopped(): bool
    {
        return isset($this->response) || isset($this->error);
    }
}
