<?php

declare(strict_types=1);

namespace Crell\MiDy\Router\HandlerRouter;

use Crell\MiDy\Router\RequestPath;
use Crell\MiDy\Router\RouteMethodNotAllowed;
use Crell\MiDy\Router\RouteNotFound;
use Crell\MiDy\Router\Router;
use Crell\MiDy\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;
use Webmozart\Glob\Glob;

class HandlerRouter implements Router
{
    public function __construct(
        private readonly string $routesPath,
    ) {}

    /**
     * @var array<string, array<string, array<PageHandler>>>
     */
    private array $handlerMap = [];

    public function addHandler(PageHandler $handler): void
    {
        foreach ($handler->supportedMethods() as $method) {
            foreach ($handler->supportedExtensions() as $ext) {
                $this->handlerMap[$ext][$method][] = $handler;
            }
        }
    }

    public function route(ServerRequestInterface $request): RouteResult
    {
        $requestPath = $request->getAttribute(RequestPath::class);
        $method = $request->getMethod();

        $candidates = $this->getFilePaths($requestPath);

        if (empty($candidates)) {
            return new RouteNotFound();
        }

        $possibleMethods = [];
        foreach ($candidates as $candidate) {
            $ext = pathinfo($candidate, PATHINFO_EXTENSION);
            $possibleMethods += $this->handlerMap[$ext] ?? [];

            /** @var PageHandler $handler */
            foreach ($this->handlerMap[$ext][$method] ?? [] as $handler) {
                if ($result = $handler->handle($request, $candidate, $ext)) {
                    return $result;
                }
            }
        }

        // There was a candidate, so it's not unfound. But
        // nothing handled it, which means nothing could deal with
        // that file type and method.  So we'll call that a method error.
        return new RouteMethodNotAllowed($possibleMethods);
    }

    private function getFilePaths(RequestPath $requestPath): array
    {
        return Glob::glob("{$this->routesPath}{$requestPath->normalizedPath}.{$requestPath->ext}");
    }
}
