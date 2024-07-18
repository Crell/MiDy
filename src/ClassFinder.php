<?php

declare(strict_types=1);

namespace Crell\MiDy;

class ClassFinder
{
    /**
     * @return iterable<class-string>
     */
    public function find(string $directory): iterable
    {
        // This should probably move to use a composer plugin to pre-build the index,
        // but this will do for now.

        $dirIterator = new \RecursiveDirectoryIterator($directory);
        $iterator = new \RecursiveIteratorIterator($dirIterator);
        $files = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        foreach ($files as $file) {
            $body = file_get_contents($file[0]);
            if (!$body) {
                continue;
            }
            if ($className = $this->extractClassName($body)) {
                yield $className;
            }
        }
    }

    /**
     * @return class-string|null
     */
    private function extractClassName(string $file): ?string
    {
        $namespace = null;
        $className = null;

        $tokens = \PhpToken::tokenize($file);
        $count = count($tokens);

        for ($i = 2; $i < $count && !($namespace && $className); $i++) {
            if ($tokens[$i - 2]->id === T_NAMESPACE
                && $tokens[$i - 1]->id === T_WHITESPACE
                && $tokens[$i]->id === T_NAME_QUALIFIED
            ) {
                $namespace = $tokens[$i]->text;
            } elseif($tokens[$i - 2]->id === T_CLASS
                && $tokens[$i - 1]->id === T_WHITESPACE
                && $tokens[$i]->id === T_STRING
            ) {
                $className = $tokens[$i]->text;
            }
        }

        if ($namespace && $className) {
            /** @var class-string $name */
            $name = $namespace . '\\' . $className;
            return $name;
        }
        return null;
    }
}
