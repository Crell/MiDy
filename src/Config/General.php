<?php

declare(strict_types=1);

namespace Crell\MiDy\Config;

use Crell\Config\Config;

#[Config('general')]
readonly class General
{
    public function __construct(
        // The path within the routes directory that contains error pages and similar templates.
        public string $specialFilesPath = 'special',
    ) {}
}
