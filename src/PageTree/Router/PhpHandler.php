<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Router;

use Crell\MiDy\ClassFinder;
use Crell\MiDy\PageTree\Page;
use Crell\MiDy\Router\RouteMethodNotAllowed;
use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\Router\RouteSuccess;
use DI\FactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class PhpHandler implements SupportsTrailingPath
{
    public private(set) array $supportedMethods = ['GET', 'POST', 'HEAD', 'PUT', 'DELETE'];
    public private(set) array $supportedExtensions = ['php'];

    /**
     * @param FactoryInterface $container
     *   If we ever change containers, this will be impacted.
     */
    public function __construct(
        private readonly FactoryInterface $container,
        private readonly ClassFinder $finder,
    ) {}

    public function handle(ServerRequestInterface $request, Page $page, string $ext, array $trailing = []): ?RouteResult
    {
        // Because the actual action callable isn't in this class but in the class that gets
        // loaded later, we cannot put the cache header handling here.  The class file
        // has to do it itself.

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

        // This is all error handling.
        $rAction = new \ReflectionObject($actionObject);
        $rMethods = $rAction->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methodNames = array_map(fn(\ReflectionMethod $m) => strtoupper($m->name), $rMethods);
        $allowedMethods = array_intersect($methodNames, $this->supportedMethods);
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
