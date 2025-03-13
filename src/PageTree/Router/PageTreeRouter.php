<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Router;

use Crell\MiDy\PageTree\LogicalPath;
use Crell\MiDy\PageTree\PageTree;
use Crell\MiDy\Router\RouteMethodNotAllowed;
use Crell\MiDy\Router\RouteNotFound;
use Crell\MiDy\Router\Router;
use Crell\MiDy\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

class PageTreeRouter implements Router
{
    public function __construct(
        private readonly PageTree $tree,
    ) {}

    /**
     * @var PageHandler
     */
    private array $handlerMap = [];

    public function addHandler(PageHandler $handler): void
    {
        foreach ($handler->supportedMethods as $method) {
            foreach ($handler->supportedExtensions as $ext) {
                $this->handlerMap[$ext][$method][] = $handler;
            }
        }
    }

    public function route(ServerRequestInterface $request): RouteResult
    {
        $method = $request->getMethod();

        $requestPath = LogicalPath::create($request->getUri()->getPath());

        [$page, $trailing] = $this->getPage($requestPath);

        if ($page === null) {
            return new RouteNotFound();
        }

        if (!$page->routable) {
            return new RouteNotFound();
        }

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

    /**
     * Retrieves a page, taking trailing arguments into account.
     */
    private function getPage(LogicalPath $logicalPath): array
    {
        $path = $logicalPath;

        $tail = [];
        do {
            $page = $this->tree->page($path->withoutExtension);
        } while ($page === null && ($tail[] = $path->end) && ($path = $path->parent()) && $path != '/');

        $tail = array_reverse($tail);

        if ($path->ext) {
            return [$page?->variant($path->ext), $tail];
        }
        return [$page, $tail];
    }
}
