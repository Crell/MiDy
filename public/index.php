<?php

declare(strict_types=1);

use HttpSoft\Emitter\SapiEmitter;
use Nyholm\Psr7Server\ServerRequestCreator;

require __DIR__ . '/../vendor/autoload.php';

$app = new \Crell\MiDy\MiDy(routesPath: \realpath('../tests/test-routes'));
//$app = new \Crell\MiDy\MiDy();

$serverRequest = $app->container->get(ServerRequestCreator::class)->fromGlobals();

$response = $app->handle($serverRequest);

$app->container->get(SapiEmitter::class)->emit($response);
