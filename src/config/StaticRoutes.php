<?php

declare(strict_types=1);

namespace Crell\MiDy\config;

use Crell\Config\Config;

#[Config('static_routes')]
readonly class StaticRoutes
{
    public function __construct(
        public array $allowedExtensions = [
            'html' => 'text/html',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'svg' => 'image/svg',
            'jpg' => 'image/jpg',
            'webm' => 'image/webm',
        ],
    ) {}
}
