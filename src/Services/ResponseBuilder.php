<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

readonly class ResponseBuilder
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function createResponse(int $code, string $body, ?string $contentType = null): ResponseInterface
    {
        $stream = $this->streamFactory->createStream($body);
        $stream->rewind();
        $response = $this->responseFactory
            ->createResponse($code)
            ->withBody($stream);
        if ($contentType) {
            $response = $response->withHeader('content-type', $contentType);
        }

        return $response;
    }

    public function ok(string $body, ?string $contentType = null): ResponseInterface
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

    public function notFound(string $body, ?string $contentType = null): ResponseInterface
    {
        return $this->createResponse(404, $body, $contentType);
    }

    public function forbidden(string $body, ?string $contentType = null): ResponseInterface
    {
        return $this->createResponse(403, $body, $contentType);
    }

    public function gone(string $body, ?string $contentType = null): ResponseInterface
    {
        return $this->createResponse(410, $body, $contentType);
    }

}
