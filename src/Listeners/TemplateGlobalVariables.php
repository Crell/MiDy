<?php

declare(strict_types=1);

namespace Crell\MiDy\Listeners;

use Crell\MiDy\Config\TemplateVariables;
use Crell\MiDy\Services\TemplatePreRender;
use Crell\Tukio\Listener;

#[Listener]
readonly class TemplateGlobalVariables
{
    public function __construct(
        private TemplateVariables $templateVariables,
    ) {}

    public function __invoke(TemplatePreRender $event): void
    {
        $event->args += $this->templateVariables->variables;
    }
}
