<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTreeDB2;

interface Hidable
{
    public bool $hidden { get; }
}