<?php

declare(strict_types=1);

use Crell\MiDy\MiDy;

require __DIR__ . '/../vendor/autoload.php';

$midy = new MiDy();
if (!($_SERVER['FRANKENPHP_WORKER'] ?? false)) {
    $midy->run();
    return;
}

while (\frankenphp_handle_request(static fn() => $midy->run())) {
    gc_collect_cycles();
}
