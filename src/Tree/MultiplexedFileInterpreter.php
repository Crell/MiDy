<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

class MultiplexedFileInterpreter implements FileInterpreter
{
    /**
     * @var array<string, array<FileInterpreter>>
     */
    private array $interpreters = [];

    public function addInterpreter(FileInterpreter $interpreter): void
    {
        foreach ($interpreter->supportedExtensions() as $ext) {
            $this->interpreters[$ext][] = $interpreter;
        }
    }

    public function supportedExtensions(): array
    {
        return array_keys($this->interpreters);
    }

    public function map(\SplFileInfo $fileInfo, string $parentLogicalPath): RouteFile|FileInterpreterError
    {
        /** @var FileInterpreter $i */
        foreach ($this->interpreters[$fileInfo->getExtension()] ?? [] as $i) {
            if (($routeFile = $i->map($fileInfo, $parentLogicalPath)) instanceof RouteFile) {
                return $routeFile;
            }
        }

        return FileInterpreterError::FileNotSupported;
    }
}
