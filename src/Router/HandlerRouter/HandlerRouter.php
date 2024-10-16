<?php

declare(strict_types=1);

namespace Crell\MiDy\Router\HandlerRouter;

use Crell\MiDy\PageHandlers\SupportsTrailingPath;
use Crell\MiDy\PageTree\Folder;
use Crell\MiDy\PageTree\OldFolder;
use Crell\MiDy\PageTree\RootFolder;
use Crell\MiDy\Router\RouteMethodNotAllowed;
use Crell\MiDy\Router\RouteNotFound;
use Crell\MiDy\Router\Router;
use Crell\MiDy\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

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

        $page = $this->root->route($request->getUri()->getPath());

        // A folder with no index page is non-routable.
        if ($page instanceof Folder && !$page->indexPage) {
            return new RouteNotFound();
        }

        $trailing = $page->getTrailingPath($request->getUri()->getPath());

        $possibleMethods = [];
        foreach ($page->variants() as $ext => $file) {
            $possibleMethods += $this->handlerMap[$ext] ?? [];

            /** @var PageHandler $handler */
            foreach ($this->handlerMap[$ext][$method] ?? [] as $handler) {
                if ($trailing) {
                    if ($handler instanceof SupportsTrailingPath) {
                        if ($result = $handler->handle($request, $page, $ext, $trailing)) {
                            return $result;
                        }
                    }
                } elseif ($result = $handler->handle($request, $page, $ext)) {
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
