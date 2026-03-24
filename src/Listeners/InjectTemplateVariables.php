<?php

namespace Crell\MiDy\Listeners;

use Crell\MiDy\Config\TemplateVariables;
use Crell\MiDy\Services\TemplatePreRender;

readonly class InjectTemplateVariables
{
    public function __construct(
        private TemplateVariables $templateVariables,
    ) {}

    public function __invoke(TemplatePreRender $event): void
    {
        foreach ($this->templateVariables as $k => $v) {
            $event->args[$k] ??= $v;
        }
    }
}
