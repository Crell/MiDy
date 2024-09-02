<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

interface Titled
{
    public function title(): string;
}