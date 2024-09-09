<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

interface Linkable extends Titled
{
    public function path(): string;
}