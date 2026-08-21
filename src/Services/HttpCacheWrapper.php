<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

use Crell\Carica\ResponseBuilder;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HttpCacheWrapper
{
    public const string EtagHashAlgorithm = 'xxh3';

    public function __construct(
        private ResponseBuilder $responseBuilder,
        private ClockInterface $clock,
        private bool $enableCache = true,
        /** The cache lifetime in seconds. The default is 5 minutes. */
        private int $cacheLifetime = 300,
    ) {}

    /**
     * Wraps a request handler in HTTP cache handling, based on a specified file.
     *
     * This method only handles last-modified and ETag cache headers.  It does not
     * set cache lifetimes.  A cache lifetime set by the $generator will be left untouched.
     *
     * @param ServerRequestInterface $request
     *   The incoming request.
     * @param string $filePath
     *   The absolute (or stream) path to a file on disk. The file's last-modified time will be used
     *   for time-based comparison, and a hash of the file contents will be used for ETag comparison.
     * @param \Closure $generator
     *   The callable thunk that will generate the response if necessary.
     * @return ResponseInterface
     *   Either a 304 Not Modified response, or a new response with appropriate cache headers.
     * @throws \Exception
     */
    public function handleCacheableFileRequest(ServerRequestInterface $request, string $filePath, \Closure $generator): ResponseInterface
    {
        if (!$this->enableCache) {
            return $generator()
                ->withHeader('cache-control', 'no-cache');
        }

        $mtime = new \DateTimeImmutable('@' . filemtime($filePath));
        $etag = hash_file(self::EtagHashAlgorithm, $filePath);

        // If the page is older than the cache lifetime, we'll regenerate it anyway.
        // That way, pages that transclude other pages (like listings) will regenerate eventually.
        if ($mtime->modify("+ $this->cacheLifetime seconds") < $this->clock->now()) {
            return $this->respond($generator(), $mtime, $etag);
        }

        $ifModifiedSince = $request->getHeaderLine('if-modified-since');

        if ($ifModifiedSince && new \DateTimeImmutable($ifModifiedSince) >= $mtime) {
            return $this->responseBuilder->notModified();
        }
        if ($request->getHeaderLine('if-none-match') === $etag) {
            return $this->responseBuilder->notModified();
        }

        return $this->respond($generator(), $mtime, $etag);
    }

    private function respond(ResponseInterface $response, \DateTimeImmutable $mtime, string $etag): ResponseInterface
    {
        return $response
            ->withHeader('last-modified', $mtime->format('r'))
            ->withHeader('etag', $etag)
            ->withHeader('cache-control', sprintf('max-age: %d', $this->cacheLifetime));
    }
}
