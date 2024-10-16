<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

interface Hidable
{
    public bool $hidden { get; }
}