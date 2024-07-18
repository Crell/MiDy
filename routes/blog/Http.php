<?php

declare(strict_types=1);

namespace App\Routes\blog;

use Crell\MiDy\Services\ResponseBuilder;
use Psr\Http\Message\ResponseInterface;

class Http
{
    public function __construct(
        private ResponseBuilder $builder,
    ) {}

    public function get(): ResponseInterface
    {
        return $this->builder->ok("Blog page");
    }
}
