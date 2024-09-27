<?php

declare(strict_types=1);

namespace Crell\MiDy\Listeners;

use Crell\MiDy\Config\TemplateVariables;
use Crell\MiDy\PageTree\RootFolder;
use Crell\MiDy\Services\TemplatePreRender;
use Crell\Tukio\Listener;
use DI\Attribute\Inject;

#[Listener]
readonly class TemplateGlobalVariables
{
    public function __construct(
        private TemplateVariables $templateVariables,
        #[Inject('paths.templates')]
        private string $templatePath,
        private RootFolder $root,
    ) {}

    public function __invoke(TemplatePreRender $event): void
    {
        $event->args += $this->templateVariables->variables;
        $event->args += ['templateRoot' => $this->templatePath];
        $event->args += ['root' => $this->root];
    }
}
