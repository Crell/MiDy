<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;
use Crell\MiDy\MiDy;

require __DIR__ . '/vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/.env');

$app = new MiDy('.');

/** @var \Crell\MiDy\Commands\CleanGeneratedFiles $cmd */
$cmd = $app->container->get(\Crell\MiDy\Commands\CleanGeneratedFiles::class);

$cmd->run();
