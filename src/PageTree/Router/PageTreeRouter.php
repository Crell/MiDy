<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Router;

use Crell\Carica\Router\RouteMethodNotAllowed;
use Crell\Carica\Router\RouteNotFound;
use Crell\Carica\Router\Router;
use Crell\Carica\Router\RouteResult;
use Crell\MiDy\PageTree\LogicalPath;
use Crell\MiDy\PageTree\Page;
use Crell\MiDy\PageTree\PageTree;
use Psr\Http\Message\ServerRequestInterface;

class PageTreeRouter implements Router
{
    public function __construct(
        private readonly PageTree $tree,
    ) {}

    /**
     * @var array<string, array<string, PageHandler>>
     *   A lookup table for the handlers, based on extension and method.
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
     *
     * @return array{0: ?Page, 1: list<string>}
     */
    private function getPage(LogicalPath $logicalPath): array
    {
        $path = $logicalPath;

        $tail = [];
        do {
            $page = $this->tree->page($path->withoutExtension);
        // @phpstan-ignore-next-line booleanAnd.rightAlwaysTrue (We're using the while clause to assign values, which is always true by definition)
        } while ($page === null && ($tail[] = $path->end) && ($path = $path->parent()) && $path != '/');

        $tail = array_reverse($tail);

        if ($path->ext) {
            return [$page?->variant($path->ext), $tail];
        }
        return [$page, $tail];
    }
}
