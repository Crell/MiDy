<?php

declare(strict_types=1);

use Crell\MiDy\Services\PrintLogger;
use Crell\MiDy\ActionRunner;
use Crell\MiDy\Middleware\CacheMiddleware;
use Crell\MiDy\Middleware\DeriveFormatMiddleware;
use Crell\MiDy\Middleware\EnforceHeadMiddleware;
use Crell\MiDy\Middleware\LogMiddleware;
use Crell\MiDy\Middleware\ParamConverterMiddleware;
use Crell\MiDy\Middleware\RoutingMiddleware;
use Crell\MiDy\StackMiddlewareKernel;
use Crell\MiDy\ClassFinder;
use Crell\Tukio\DebugEventDispatcher;
use Crell\Tukio\Dispatcher;
use DI\ContainerBuilder;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Crell\Tukio\OrderedListenerProvider;

use function DI\autowire;
use function DI\get;

require __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);

$finder = new ClassFinder();

$paths = [
    '../src/',
];

foreach ($paths as $path) {
    foreach ($finder->find($path) as $class) {
        $containerBuilder->addDefinitions([
            $class => autowire(),
        ]);
    }
}

$containerBuilder->addDefinitions([
    StackMiddlewareKernel::class => autowire(StackMiddlewareKernel::class)
        ->constructor(baseHandler: get(ActionRunner::class))
        // These will run last to first, ie, the earlier listed ones are "more inner."
        // That makes interlacing request, response, and "both" middlewares tricky.
        ->method('addMiddleware', get(ParamConverterMiddleware::class))
//        ->method('addMiddleware', get(AuthorizationMiddleware::class))
        ->method('addMiddleware', get(RoutingMiddleware::class))
        ->method('addMiddleware', get(DeriveFormatMiddleware::class))
//        ->method('addMiddleware', get(AuthenticationMiddleware::class))
        ->method('addMiddleware', get(CacheMiddleware::class))
        ->method('addMiddleware', get(EnforceHeadMiddleware::class))
        ->method('addMiddleware', get(LogMiddleware::class))
    ,
    SapiEmitter::class => autowire(SapiEmitter::class)
    ,
    Dispatcher::class => autowire(),
            DebugEventDispatcher::class => autowire()
    ->constructorParameter('dispatcher', get(Dispatcher::class)),
            OrderedListenerProvider::class => autowire(),
            ListenerProviderInterface::class => get(OrderedListenerProvider::class),
            EventDispatcherInterface::class => get(Dispatcher::class),

            NullLogger::class => autowire(),
            PrintLogger::class => autowire(),
            LoggerInterface::class => get(NullLogger::class),

            ResponseFactoryInterface::class => get(Psr17Factory::class),
            StreamFactoryInterface::class => get(Psr17Factory::class),
            RequestFactoryInterface::class => get(Psr17Factory::class),
            ServerRequestFactoryInterface::class => get(Psr17Factory::class),
    ]);

$container = $containerBuilder->build();

$psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();

$creator = new \Nyholm\Psr7Server\ServerRequestCreator(
    $psr17Factory, // ServerRequestFactory
    $psr17Factory, // UriFactory
    $psr17Factory, // UploadedFileFactory
    $psr17Factory  // StreamFactory
);

$serverRequest = $creator->fromGlobals();

$response = $container->get(StackMiddlewareKernel::class)->handle($serverRequest);

$container->get(SapiEmitter::class)->emit($response);
