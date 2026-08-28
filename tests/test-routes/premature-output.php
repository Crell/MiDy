<?php

declare(strict_types=1);

use Crell\Carica\ResponseBuilder;
use Psr\Http\Message\ResponseInterface;

class PrematureOutput
{
    public function __construct(
        private ResponseBuilder $builder,
    ) {}

    public function get(): ResponseInterface
    {
        print "This is incorrect.\n";

        return $this->builder->ok('Display this');
    }
}
