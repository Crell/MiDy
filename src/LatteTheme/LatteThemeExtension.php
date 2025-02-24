<?php

declare(strict_types=1);

namespace Crell\MiDy\LatteTheme;

use Latte\Extension;
use Latte\RuntimeException;

/**
 * Extension for simulating theme behavior.
 *
 * Templates can live in a series of configurable directories.
 * By using the template() function to specify the layout parent
 * (or any other template, really), this extension will search
 * through the list of directories in order to find the appropriate
 * file.  That allows a main "theme" to exist, with selective overrides
 * on a specific site. It also allows the core application to ship
 * with default templates (like html.latte), which create an
 * automatic API for any templates trying to extend the application.
 */
class LatteThemeExtension extends Extension
{
    /** @var string[] */
    private array $themes;

    public function __construct(
        private string $allowedRoot,
        string $core,
        string $site,
        ?string $theme = null,
    ) {
        $this->themes = array_filter([$theme, $site, $core]);
    }

    public function getFunctions(): array
    {
        return [
            'template' => $this->findTemplatePath(...),
        ];
    }

    public function findTemplatePath(string $name): string
    {
        foreach ($this->themes as $theme) {
            $candidate = $theme . '/' . $name;
            if (str_starts_with($candidate, $this->allowedRoot) && file_exists($candidate)) {
                return $candidate;
            }
        }
        throw new RuntimeException("Missing template file '$name'.");
    }
}
