<?php

declare(strict_types=1);

namespace Crell\MiDy\Router\HandlerRouter;

use Crell\MiDy\Router\RequestPath;
use Crell\MiDy\Router\RouteMethodNotAllowed;
use Crell\MiDy\Router\RouteNotFound;
use Crell\MiDy\Router\Router;
use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\Tree\RootFolder;
use Psr\Http\Message\ServerRequestInterface;
use Webmozart\Glob\Glob;

class HandlerRouter implements Router
{
    public function __construct(
        private readonly RootFolder $root,
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
        $method = $request->getMethod();

        $page = $this->root->find($request->getUri()->getPath());

        if (!$page) {
            return new RouteNotFound();
        }

        $possibleMethods = [];
        foreach ($page->variants() as $ext => $file) {
            $possibleMethods += $this->handlerMap[$ext] ?? [];

            /** @var PageHandler $handler */
            foreach ($this->handlerMap[$ext][$method] ?? [] as $handler) {
                if ($result = $handler->handle($request, $page, $ext)) {
                    return $result;
                }
            }
        }

        // There was a candidate, so it's not unfound. But
        // nothing handled it, which means nothing could deal with
        // that file type and method.  So we'll call that a method error.
        return new RouteMethodNotAllowed(array_keys($possibleMethods));
    }
}
