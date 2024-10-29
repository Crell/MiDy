<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/.env');

$app = new \Crell\MiDy\MiDy('.');

/** @var \Crell\MiDy\Commands\StaticFilePregenerator $cmd */
$cmd = $app->container->get(\Crell\MiDy\Commands\StaticFilePregenerator::class);

$cmd->run();
