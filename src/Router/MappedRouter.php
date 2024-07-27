<?php

declare(strict_types=1);

namespace Crell\MiDy\Router;

use Crell\MiDy\PageHandlers\BlogHandler;
use Crell\MiDy\PageHandlers\LatteHandler;
use Crell\MiDy\PageHandlers\MarkdownLatteHandler;
use Crell\MiDy\PageHandlers\PhpHandler;
use Crell\MiDy\PageHandlers\StaticFileHandler;
use Psr\Http\Message\ServerRequestInterface;

class MappedRouter implements Router
{
    private array $map = [];

    public function __construct(
        private string $routesPath,
    ) {
        $this->map = [
            '/aggregateblog' => [
                BlogHandler::class => ['md'],
                StaticFileHandler::class => [],
            ],
            '/' => [
                MarkdownLatteHandler::class => ['md'],
                LatteHandler::class => ['latte'],
                PhpHandler::class => ['php'],
                StaticFileHandler::class => [],
            ],
        ];
    }

    public function route(ServerRequestInterface $request): RouteResult
    {
        // Just for now.
        return new RouteNotFound();


        $requestPath = $request->getAttribute(RequestPath::class);

        //[$requestPath, $ext] = $this->getRequestPath($request);
//        $candidates = $this->getFilePaths($requestPath, $ext);

        //$map['prefix']['method']['ext'] = 'handler';

        foreach ($requestPath->prefixes as $prefix) {
            if (isset($this->map[$prefix])) {
                $candidates = $this->getFilePaths($requestPath->requestPath, $requestPath->ext);
                var_dump($candidates);
                foreach ($this->map[$prefix] as $handler => $extensions) {
                    if ((!$extensions || in_array($requestPath->ext, $extensions, strict: true))) {

                        return new RouteSuccess(
                            action: $handler,
                            method: $request->getMethod(),
                            vars: [
                                'requestPath' => $requestPath,
                                'ext' => $extensions

//                                'file' => "$requestPath.$ext",
                            ],
                        );
                    }
                }
            }
        }

        return new RouteNotFound();

//        foreach ($this->map as $prefix => $handlers) {
//            if (str_starts_with($requestPath, $prefix)) {
//                foreach ($handlers as $handler => $extensions) {
//                    if ((!$extensions || in_array($ext, $extensions, strict: true))) {
//                        return new RouteSuccess(
//                            action: $handler,
//                            method: $request->getMethod(),
//                            vars: [
//                                'file' => "$requestPath.$ext",
//                            ],
//                        );
//                    }
//                }
//            }
//        }
//
//        if (!$candidates) {
//
//        }
//
//        return $event->routeResult ?? new RouteMethodNotAllowed([]);
    }

    private function getFilePaths(string $requestPath, string $ext = '*'): array
    {
        return glob("$requestPath.$ext");
    }
}
