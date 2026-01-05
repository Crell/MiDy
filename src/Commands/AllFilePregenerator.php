<?php

declare(strict_types=1);

namespace Crell\MiDy\Commands;

use Crell\MiDy\Config\StaticRoutes;
use Crell\MiDy\PageTree\PageCache;
use Crell\Carica\StackMiddlewareKernel;
use DI\Attribute\Inject;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;

use function Crell\MiDy\ensure_dir;

readonly class AllFilePregenerator
{
    private array $extensionLookup;

    public function __construct(
        private PageCache $cache,
        private StaticRoutes $staticRoutes,
        #[Inject('paths.public')]
        private string $publicPath,
        private StackMiddlewareKernel $kernel,
        private StaticFilePregenerator $staticPregenerator,
        private ServerRequestCreator $requestCreator,
    ) {
        $this->extensionLookup = array_flip($this->staticRoutes->allowedExtensions);
    }

    public function run(): void
    {
        // First do all the static files.  This may cause some redundancy, but we
        // need to handle those differently.
        $this->staticPregenerator->run();

        // The static pregenerator will have already reindexed the site, so
        // we don't need to do it again.

        $baseRequest = $this->makeRequest();

        foreach ($this->cache->allPaths() as $path) {
            $this->generatePage($path, $baseRequest);
        }
    }

    protected function generatePage(string $path, ServerRequestInterface $baseRequest): void
    {
        $response = $this->kernel->handle($baseRequest->withUri($baseRequest->getUri()->withPath($path)));

        $contentType = $response->getHeader('content-type')[0] ?? null;
        print "Generating $path\n";
        if ($contentType === 'text/html') {
            // Generate an index.html file for every page, so it looks like there are no extensions,
            // just like when the page is built dynamically.
            $dest = $this->publicPath . $path . '/index.html';
        } elseif (isset($this->extensionLookup[$contentType])) {
            $dest = $this->publicPath . $path . '.' . $this->extensionLookup[$contentType];
        } else {
            $dest = $this->publicPath . $path;
        }

        ensure_dir(dirname($dest));
        file_put_contents($dest, $response->getBody()->getContents());
    }

    protected function makeRequest(): ServerRequestInterface
    {
        return $this->requestCreator->fromArrays(
            server: [
                'REQUEST_METHOD' => 'GET',
            ],
            headers: [
                'Accept' => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/png,image/svg+xml,*/*;q=0.8",
            ],
        );
    }
}
