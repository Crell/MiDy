<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree\Router;

use Crell\MiDy\PageTree\Page;
use Crell\Carica\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

interface PageHandler
{
    /**
     * @var list<string>
     *     A list of the HTTP methods that this handler will accept.  Must be all uppercase.
     */
    public array $supportedMethods { get; }

    /**
     * @var list<string>
     *     A list of the file extensions this handler will accept.  Will usually be all lowercase.
     */
    public array $supportedExtensions { get; }

    public function handle(ServerRequestInterface $request, Page $page, string $ext): ?RouteResult;
}
