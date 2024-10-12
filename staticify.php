<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$app = new \Crell\MiDy\MiDy('.', routesPath: \realpath('tests/test-routes'));

/** @var \Crell\MiDy\Commands\StaticFilePregenerator $cmd */
$cmd = $app->container->get(\Crell\MiDy\Commands\AllFilePregenerator::class);

$cmd->run();
