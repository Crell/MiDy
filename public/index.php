<?php

declare(strict_types=1);

use HttpSoft\Emitter\SapiEmitter;
use Nyholm\Psr7Server\ServerRequestCreator;
use Symfony\Component\Dotenv\Dotenv;
use Crell\MiDy\MiDy;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv();
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $dotenv->loadEnv($envFile);
}

$app = new MiDy();

$serverRequest = $app->container->get(ServerRequestCreator::class)->fromGlobals();

$response = $app->handle($serverRequest);

$app->container->get(SapiEmitter::class)->emit($response);
