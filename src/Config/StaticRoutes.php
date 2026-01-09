<?php

declare(strict_types=1);

namespace Crell\MiDy\Config;

use Crell\Config\Config;

/**
 * @codeCoverageIgnore
 */
#[Config('static-routes')]
readonly class StaticRoutes
{
    /**
     * @param array<string, string> $allowedExtensions
     *   A map from a file extension to the mime-type it implies.
     */
    public function __construct(
        public array $allowedExtensions = [
            'html' => 'text/html',
            'txt' => 'text/plain',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'svg' => 'image/svg',
            'jpg' => 'image/jpg',
            'webm' => 'image/webm',
            'css' => 'text/css',
            'js' => 'application/javascript',
        ],
    ) {}
}
