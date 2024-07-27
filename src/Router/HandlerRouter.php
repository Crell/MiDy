<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

use Psr\Http\Message\ServerRequestInterface;

class HandlerRouter implements Router
{
    public function __construct(
        private string $routesPath,
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

        foreach ($candidates as $candidate) {
            $ext = pathinfo($candidate, PATHINFO_EXTENSION);
            $possibleMethods = $this->handlerMap[$ext] ?? [];
            if (!isset($possibleMethods[$method])) {
                return new RouteMethodNotAllowed($possibleMethods);
            }

            /** @var PageHandler $handler */
            foreach ($this->handlerMap[$ext][$method] as $handler) {
                if ($result = $handler->handle($request, $candidate, $ext)) {
                    return $result;
                }
            }
        }

        return new RouteNotFound();
    }

    private function getFilePaths(RequestPath $requestPath): array
    {
        return glob("{$this->routesPath}{$requestPath->normalizedPath}.{$requestPath->ext}");
    }
}
