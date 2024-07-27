<?php

declare(strict_types=1);

namespace Crell\MiDy\PageHandlers;

use Crell\MiDy\Router\PageHandler;
use Crell\MiDy\Router\RouteMethodNotAllowed;
use Crell\MiDy\Router\RouteResult;
use Crell\MiDy\Router\RouteSuccess;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class NewPhpHandler implements PageHandler
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function supportedMethods(): array
    {
        return ['GET', 'POST', 'HEAD', 'PUT', 'DELETE'];
    }

    public function supportedExtensions(): array
    {
        return ['php'];
    }

    public function handle(ServerRequestInterface $request, string $file, string $ext): ?RouteResult
    {
        $actionObject = $this->loadAction($file);

        $method = $request->getMethod();
        if (method_exists($actionObject, $method)) {
            return new RouteSuccess(
                action: $actionObject->$method(...),
                method: strtoupper($method),
                vars: [
                    'file' => $file,
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
        $container = $this->container;
        return require($file);
    }
}
