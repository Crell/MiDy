<?php

declare(strict_types=1);

namespace Crell\MiDy\Errors;

use Crell\Carica\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

readonly class NotFound implements Error
{
    public function __construct(
        public ServerRequestInterface $request,
        public RouteResult $routeResult,
    ) {}
}
