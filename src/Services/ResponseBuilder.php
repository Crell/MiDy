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

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

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
     * Checks if a request that corresponds to a file should be treated as Not Modified.
     *
     * @param ServerRequestInterface $request
     *   The incoming request.
     * @param string $filePath
     *   The absolute (or stream) path to a file on disk.  This file will be checked against
     *   both the if-modified-since header (based on the file's mtime) and if-none-match header
     *   (aka ETag, based on a hash of the file).
     * @return ResponseInterface|null
     *   Either a 304 Not Modified response object, or null if the cache check failed.
     */
    public function handleFileCacheHeaders(ServerRequestInterface $request, string $filePath): ?ResponseInterface
    {
        $ifModifiedSince = $request->getHeaderLine('if-modified-since');
        if ($ifModifiedSince && new \DateTimeImmutable($ifModifiedSince) >= new \DateTimeImmutable('@' . filemtime($filePath))) {
            return $this->notModified();
        }

        $ifNoneMatch = $request->getHeaderLine('if-none-match');
        if ($ifNoneMatch === hash_file(self::ETAG_HASH_ALGORITHM, $filePath)) {
            return $this->notModified();
        }

        return null;
    }

    /**
     * Adds cache headers to a response, based on a provided file.
     *
     * Both last-modified and etag headers will be added.
     *
     * @param ResponseInterface $response
     * @param string $filePath
     * @return ResponseInterface
     * @throws \Exception
     */
    public function withFileCacheHeaders(ResponseInterface $response, string $filePath): ResponseInterface
    {
        return $response
            ->withHeader('last-modified', (new \DateTimeImmutable('@' . filemtime($filePath)))->format('r'))
            ->withHeader('etag', hash_file(self::ETAG_HASH_ALGORITHM, $filePath));
    }

    public function handleCacheableFileRequest(ServerRequestInterface $request, string $filePath, \Closure $generator): ResponseInterface
    {
        if ($cacheResponse = $this->handleFileCacheHeaders($request, $filePath)) {
            return $cacheResponse;
        }

        return $this->withFileCacheHeaders($generator(), $filePath);
    }
}
