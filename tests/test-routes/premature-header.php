<?php

declare(strict_types=1);

use Crell\Carica\ResponseBuilder;
use Psr\Http\Message\ResponseInterface;

class PrematureHeader
{
    public function __construct(
        private ResponseBuilder $builder,
    ) {}

    public function get(): ResponseInterface
    {
        header('X-debug: 5');

        return $this->builder->ok('Display this');
    }
}
