<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

use Crell\Carica\ResponseBuilder as CaricaResponseBuilder;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ResponseBuilder extends CaricaResponseBuilder
{
    public const string ETAG_HASH_ALGORITHM = 'xxh3';

    private bool $enableCache;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        bool|string $enableCache = true,
    ) {
        parent::__construct($responseFactory, $streamFactory);
        // This is to work around a dumb bug in PHP-DI: https://github.com/PHP-DI/PHP-DI/issues/900
        $this->enableCache = match ($enableCache) {
            true, 'true', 'on', 'yes' => true,
            false, 'false', 'off', 'no' => false,
            default => false,
        };
    }

    /**
     * Wraps a request handler in HTTP cache handling, based on a specified file.
     *
     * This method only handles last-modified and ETag cache headers.  It does not
     * set cache lifetimes.  A cache lifetime set by the $generator will be left untouched.
     *
     * @todo This should move elsewhere.  Maybe even go away.
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
