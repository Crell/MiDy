<?php

declare(strict_types=1);

namespace Crell\MiDy\Tree;

class Page
{
    // @todo Need to make this non-mutable somehow, while still allowing limitTo() or equivalent.
    public int $lastModified;

    // @todo Store info about each variant here, pulled from SplFileInfo.

    /**
     * @var array<string, string>
     *   A map from extension to physical path.
     *
     * This really needs aviz.
     *
     */
    protected array $variants = [];

    /**
     * @param string $logicalPath
     * @param array<string, \SplFileInfo> $variants
     */
    public function __construct(
        private readonly string $logicalPath,
        array $variants,
    ) {
        $mtime = 0;

        /**
         * @var string $ext
         * @var \SplFileInfo $file
         */
        foreach ($variants as $ext => $file) {
            $mtime = max($mtime, $file->getMTime());
            $this->variants[$ext] = $file->getPathname();
        }
        $this->lastModified = $mtime;
    }

    // @todo This is a bad approach, and a sign that we need to merge Page and RouteFile into a single interface, probably.
    public function limitTo(string $variant): Page
    {
        $new = new Page($this->logicalPath, []);
        $new->variants[$variant] = $this->variants[$variant];
        $new->lastModified = $this->lastModified;
        return $new;
    }

    public function variants(): array
    {
        return $this->variants;
    }

    public function variant(string $ext): RouteFile
    {
        return new RouteFile(
            physicalPath: $this->variants[$ext],
            logiclPath: $this->logicalPath,
            ext: $ext,
        );
    }

    // @todo Make this better.
    public function title(): string
    {
        return ucfirst(pathinfo($this->logicalPath, PATHINFO_BASENAME));
    }

    public function path(): string
    {
        return $this->logicalPath;
    }
}
