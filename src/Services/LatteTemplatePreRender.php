<?php

declare(strict_types=1);

namespace Crell\MiDy\Services;

/**
 * This event exists only to allow a listener to care only about Latte templates.
 */
class LatteTemplatePreRender extends TemplatePreRender {}
