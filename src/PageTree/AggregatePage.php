<?php

declare(strict_types=1);

namespace Crell\MiDy\PageTree;

readonly class AggregatePage implements Page
{
    /**
     * @todo Make this lazy with a get hook.
     */
    protected Page $activePage;

    public function __construct(
        protected string $logicalPath,
        protected array $variants,
    ) {}

    // @todo We can still do better than this, seriously.
    private function activePage(): Page
    {
        return $this->activePage ??= array_values($this->variants)[0];
    }

    public function routable(): bool
    {
        return $this->activePage()->routable();
    }

    public function path(): string
    {
        return $this->logicalPath;
    }

    /**
     * @inheritDoc
     */
    public function variants(): array
    {
        return $this->variants;
    }

    public function variant(string $ext): ?Page
    {
        return $this->variants[$ext] ?? null;
    }

    public function getTrailingPath(string $fullPath): array
    {
        if (!str_starts_with($fullPath, $this->logicalPath)) {
            return [];
        }

        // If the path ends with an extension, then we assume it's a file
        // and there's no trailing necessary.
        if (pathinfo($fullPath, PATHINFO_EXTENSION)) {
            return [];
        }

        return array_values(array_filter(explode('/', substr($fullPath, strlen($this->logicalPath)))));
    }

    public function title(): string
    {
        return $this->activePage()->title();
    }

    public function summary(): string
    {
        return $this->activePage()->summary();
    }

    public function tags(): array
    {
        return $this->activePage()->tags();
    }

    public function slug(): ?string
    {
        return $this->activePage()->slug();
    }

    public function hidden(): bool
    {
        return $this->activePage()->hidden();
    }
}
