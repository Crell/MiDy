<?php

namespace Crell\MiDy;

use Psr\Clock\ClockInterface;

class FrozenClock implements ClockInterface
{
    public function __construct(private \DateTimeImmutable $now) {}

    public function set(\DateTimeImmutable $new): self
    {
        $this->now = $new;
        return $this;
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
