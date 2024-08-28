<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

interface MultiType
{
    public function limitTo(string $variant): static;

    public function variants(): array;

    public function variant(string $ext): ?RouteFile;
}
