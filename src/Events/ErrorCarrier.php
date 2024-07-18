<?php

declare(strict_types=1);

namespace Crell\MiDy\Events;

use Crell\KernelBench\Errors\Error;

trait ErrorCarrier
{
    private Error $error;

    public function setError(Error $error): static
    {
        $this->error = $error;
        return $this;
    }

    public function getError(): ?Error
    {
        return $this->error ?? null;
    }
}
