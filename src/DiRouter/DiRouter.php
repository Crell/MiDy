<?php

declare(strict_types=1);

namespace Crell\MiDy\DiRouter;

use Crell\MiDy\ClassFinder;
use Slim\App;

readonly class DiRouter
{
    public function __construct(private string $routesPath, private string $prefix = '') {}

    public function addRoutes(App $app): void
    {
        // This is a very wasteful approach, but for now, it works.  We'll make it better in the future.

        $finder = new ClassFinder();
        $classes = $finder->find($this->routesPath);

        $container = $app->getContainer();

        foreach ($classes as $class) {
            $container->set
        }

        var_dump(iterator_to_array($classes));
//
//        $it = new \RecursiveDirectoryIterator($this->routesPath, flags: \FilesystemIterator::SKIP_DOTS);
//
//        $dirIterator = new \RecursiveDirectoryIterator($this->routesPath);
//        $iterator = new \RecursiveIteratorIterator($dirIterator);
//        $it = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);
//
////        $it = new \FilesystemIterator($this->routesPath);
//
//        foreach ($it as $a) {
//            var_dump($a);
//        }
    }
}
