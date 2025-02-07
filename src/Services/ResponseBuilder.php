<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

readonly class ResponseBuilder
{
    public const string ETAG_HASH_ALGORITHM = 'xxh3';

    private bool $enableCache;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        bool|string $enableCache = true,
    ) {
        // This is to work around a dumb bug in PHP-DI: https://github.com/PHP-DI/PHP-DI/issues/900
        $this->enableCache = match ($enableCache) {
            true, 'true', 'on', 'yes' => true,
            false, 'false', 'off', 'no' => false,
            default => false,
        };
    }

    public function createResponse(int $code, string|StreamInterface $body, ?string $contentType = null): ResponseInterface
    {
        if (is_string($body)) {
            $body = $this->streamFactory->createStream($body);
            $body->rewind();
        }
        $response = $this->responseFactory
            ->createResponse($code)
            ->withBody($body);
        if ($contentType) {
            $response = $response->withHeader('content-type', $contentType);
        }

        return $response;
    }

    public function ok(string|StreamInterface $body, ?string $contentType = null): ResponseInterface
    {
        return $this->createResponse(200, $body, $contentType);
    }

    public function created(string $location): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(201)
            ->withHeader('location', $location);
    }

    public function noContent(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(204);
    }

    public function notModified(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(304);
    }

    public function seeOther(string $location): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(303)
            ->withHeader('location', $location);
    }

    public function temporaryRedirect(string $location): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(307)
            ->withHeader('location', $location);
    }

    public function permanentRedirect(string $location): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(308)
            ->withHeader('location', $location);
    }

    public function notFound(string|StreamInterface $body, ?string $contentType = null): ResponseInterface
    {
        return $this->createResponse(404, $body, $contentType);
    }

    public function forbidden(string|StreamInterface $body, ?string $contentType = null): ResponseInterface
    {
        return $this->createResponse(403, $body, $contentType);
    }

    public function gone(string|StreamInterface $body, ?string $contentType = null): ResponseInterface
    {
        return $this->createResponse(410, $body, $contentType);
    }

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
            return $generator();
        }

        $mtime = filemtime($filePath);
        $etag = hash_file(self::ETAG_HASH_ALGORITHM, $filePath);

        $ifModifiedSince = $request->getHeaderLine('if-modified-since');
        if ($ifModifiedSince && new \DateTimeImmutable($ifModifiedSince) >= new \DateTimeImmutable('@' . $mtime)) {
            return $this->notModified();
        }

        if ($request->getHeaderLine('if-none-match') === $etag) {
            return $this->notModified();
        }

        return $generator()
            ->withHeader('last-modified', (new \DateTimeImmutable('@' . $mtime))->format('r'))
            ->withHeader('etag', $etag);
    }
}
