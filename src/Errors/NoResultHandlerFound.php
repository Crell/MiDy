<?php

declare(strict_types=1);

namespace Crell\MiDy\Errors;

use Psr\Http\Message\ServerRequestInterface;

readonly class NoResultHandlerFound implements Error
{
    public function __construct(
        public ServerRequestInterface $request,
        public mixed $result,
    ) {}
}
