<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

interface Folder extends \Countable, \IteratorAggregate
{
    public function child(string $name): Folder|Page|null;
}
