<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlers;

use Crell\MiDy\Services\ResponseBuilder;
use Latte\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class LatteHandler
{
    public function __construct(
        private ResponseBuilder $builder,
        private Engine $latte,
    ) {}

    public function __invoke(ServerRequestInterface $request, string $file): ResponseInterface
    {
        $page = $this->latte->renderToString($file);

        return $this->builder->ok($page);
    }
}