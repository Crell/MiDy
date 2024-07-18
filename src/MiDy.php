<?php

declare(strict_types=1);

namespace Crell\MiDy;

use Crell\MiDy\Middleware\CacheMiddleware;
use Crell\MiDy\Middleware\DeriveFormatMiddleware;
use Crell\MiDy\Middleware\EnforceHeadMiddleware;
use Crell\MiDy\Middleware\LogMiddleware;
use Crell\MiDy\Middleware\ParamConverterMiddleware;
use Crell\MiDy\Middleware\RoutingMiddleware;
use Crell\MiDy\Router\Router;
use Crell\MiDy\Services\ActionInvoker;
use Crell\MiDy\Services\PrintLogger;
use Crell\MiDy\Services\RuntimeActionInvoker;
use Crell\MiDy\Services\Templates;
use Crell\Tukio\DebugEventDispatcher;
use Crell\Tukio\Dispatcher;
use Crell\Tukio\OrderedListenerProvider;
use DI\ContainerBuilder;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Latte\Engine;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function DI\autowire;
use function DI\get;
use function DI\value;

class MiDy
{
    public function buildContainer(): ContainerInterface
    {

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(true);

        $finder = new ClassFinder();

        $codePaths = [
            '../src/',
        ];

        foreach ($codePaths as $path) {
            foreach ($finder->find($path) as $class) {
                $containerBuilder->addDefinitions([
                    $class => autowire(),
                ]);
            }
        }

        $routesPath = \realpath('../routes');

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
            Router::class => autowire()->constructorParameter('routesPath', $routesPath),
            ActionInvoker::class => get(RuntimeActionInvoker::class)
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

            // HTTP handling.
            ResponseFactoryInterface::class => get(Psr17Factory::class),
            StreamFactoryInterface::class => get(Psr17Factory::class),
            RequestFactoryInterface::class => get(Psr17Factory::class),
            ServerRequestFactoryInterface::class => get(Psr17Factory::class),
            UriFactoryInterface::class => get(Psr17Factory::class),

            ServerRequestCreator::class => autowire()
                ->constructor(
                    serverRequestFactory: get(ServerRequestFactoryInterface::class),
                    uriFactory: get(Psr17Factory::class),
                    uploadedFileFactory: get(Psr17Factory::class),
                    streamFactory: get(StreamFactoryInterface::class),
                )
            ,
            // Latte templates
            'latte.cache' => value('cache/latte'),
            Engine::class => autowire()->method('setTempDirectory', get('latte.cache')),
            Templates::class => autowire()->constructor(templateDirectory: 'templates')
        ]);

        return $containerBuilder->build();
    }

    public function run(): void
    {
        $container = $this->buildContainer();

        $serverRequest = $container->get(ServerRequestCreator::class)->fromGlobals();

        $response = $container->get(StackMiddlewareKernel::class)->handle($serverRequest);

        $container->get(SapiEmitter::class)->emit($response);
    }

}