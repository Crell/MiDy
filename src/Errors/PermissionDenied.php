<?php

declare(strict_types=1);

namespace Crell\MiDy\Errors;

use Crell\KernelBench\Documents\User;
use Psr\Http\Message\ServerRequestInterface;

readonly class PermissionDenied implements Error
{
    public function __construct(
        public ServerRequestInterface $request,
        public User $user,
        public string $permission,
    ) {}
}
