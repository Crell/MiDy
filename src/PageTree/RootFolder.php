<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

class RootFolder extends Folder
{
    /**
     * @param array<string, Page> $pages
     * @param array<string, RouteProvider> $providers
     */
    public function __construct(
        string $urlPath,
        array|ProviderMap $providers,
        string $title = 'Home',
    ) {
        if (is_array($providers)) {
            $newProviders = [];
            foreach ($providers as $prefix => $provider) {
                $newProviders[$urlPath . ltrim($prefix, '/')] = $provider;
            }
            $providers = new ProviderMap($newProviders);
        }
        parent::__construct($urlPath, $providers, $title);
    }
}
