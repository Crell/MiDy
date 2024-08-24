<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

interface FileInterpreter
{
    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath): RouteFile;
}