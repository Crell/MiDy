<?php

declare(strict_types=1);

namespace Crell\MiDy\Events;

use Psr\Http\Message\ResponseInterface;

trait ResponseCarrier
{
    private ResponseInterface $response;

    public function setResponse(ResponseInterface $response): static
    {
        $this->response = $response;
        return $this;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response ?? null;
    }
}
