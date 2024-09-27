<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlers;

use Crell\MiDy\ClassFinder;
use Crell\MiDy\PageTree\Page;
use Crell\MiDy\Router\RouteMethodNotAllowed;
use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\Router\RouteSuccess;
use DI\FactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class PhpHandler implements SupportsTrailingPath
{
    /**
     * @param FactoryInterface $container
     *   If we ever change containers, this will be impacted.
     */
    public function __construct(
        private FactoryInterface $container,
        private ClassFinder $finder,
    ) {}

    public function supportedMethods(): array
    {
        return ['GET', 'POST', 'HEAD', 'PUT', 'DELETE'];
    }

    public function supportedExtensions(): array
    {
        return ['php'];
    }

    public function handle(ServerRequestInterface $request, Page $page, string $ext, array $trailing = []): ?RouteResult
    {
        $actionObject = $this->loadAction($page->variant($ext)->physicalPath);

        $method = $request->getMethod();
        if (method_exists($actionObject, $method)) {
            return new RouteSuccess(
                action: $actionObject->$method(...),
                method: strtoupper($method),
                vars: [
                    'file' => $page,
                    'trailing' => $trailing,
                ],
            );
        }

        $rAction = new \ReflectionObject($actionObject);

        $rMethods = $rAction->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methodNames = array_map(fn(\ReflectionMethod $m) => strtoupper($m->name), $rMethods);

        $allowedMethods = array_intersect($methodNames, $this->supportedMethods());

        return new RouteMethodNotAllowed($allowedMethods);
    }

    private function loadAction(string $file): object
    {
        require_once $file;
        $class = $this->finder->getClass($file);
        // @todo Null/error handling.
        return $this->container->make($class);
    }
}
