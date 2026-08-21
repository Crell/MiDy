<?php

namespace Crell\MiDy;

readonly class EnvSettings
{
    public function __construct(
        public bool $appDebug = false,
        public bool $httpCacheEnable = true,
        public int $httpCacheLifetime = 300,
        public ?string $cachePath = 'cache',
        public ?string $routePath = 'routes',
        public ?string $configPath = 'configuration',
        public ?string $templatesPath = 'templates',
        public ?string $publicPath = 'public',
        public ?string $baseUrl = 'http://localhost/',
    ) {}
}
