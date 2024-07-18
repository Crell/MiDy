<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

use Psr\Http\Message\ServerRequestInterface;

interface ActionInvoker
{
    public function invokeAction(ServerRequestInterface $request): mixed;
}
