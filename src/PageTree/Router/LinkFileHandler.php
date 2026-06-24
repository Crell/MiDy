<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Router;

use Crell\Carica\HttpStatus;
use Crell\Carica\ResponseBuilder;
use Crell\Carica\Router\RouteResult;
use Crell\Carica\Router\RouteSuccess;
use Crell\MiDy\PageTree\Page;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LinkFileHandler implements PageHandler
{
    private(set) array $supportedMethods = ['GET'];
    public private(set) array $supportedExtensions = ['link'];

    public function __construct(
        private readonly ResponseBuilder $builder,
    ) {}

    public function handle(ServerRequestInterface $request, Page $page, string $ext): ?RouteResult
    {
        return new RouteSuccess(
            action: $this->action(...),
            arguments: [
                'file' => $page->variant($ext)->physicalPath,
                'location' => $page->other['location'],
                'code' => $page->other['code'] ?? HttpStatus::PermanentRedirect->value,
            ],
        );
    }

    public function action(int $code, string $location): ResponseInterface
    {
        return $this->builder->createResponse($code, '')->withHeader('location', $location);
    }
}
