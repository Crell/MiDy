<?php

declare(strict_types=1);

namespace Crell\MiDy;

use Crell\MiDy\Middleware\CacheMiddleware;
use Crell\MiDy\Middleware\DeriveFormatMiddleware;
use Crell\MiDy\Middleware\EnforceHeadMiddleware;
use Crell\MiDy\Middleware\LogMiddleware;
use Crell\MiDy\Middleware\ParamConverterMiddleware;
use Crell\MiDy\Middleware\RoutingMiddleware;
use Crell\MiDy\Router\EventRouter;
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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function DI\autowire;
use function DI\get;
use function DI\value;

class MiDy implements RequestHandlerInterface
{
    public readonly ContainerInterface $container;

    public function __construct()
    {
        $this->container = $this->buildContainer();
        $this->setupListeners();
    }

    protected function buildContainer(): ContainerInterface
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
            EventRouter::class => autowire()->constructorParameter('routesPath', $routesPath),
            ActionInvoker::class => get(RuntimeActionInvoker::class)
            ,
            // Tukio Event Dispatcher
            Dispatcher::class => autowire(),
            DebugEventDispatcher::class => autowire()
                ->constructorParameter('dispatcher', get(Dispatcher::class)),
            OrderedListenerProvider::class => autowire()
                ->constructorParameter('container', get(ContainerInterface::class)),
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
            'latte.cache' => value('../cache/latte'),
            'latte.templates' => value('../templates'),
            Engine::class => autowire()
                ->method('setTempDirectory', get('latte.cache')),
            Templates::class => autowire()->constructor(templateDirectory: 'templates')
        ]);

        return $containerBuilder->build();
    }


    public function setupListeners(): void
    {
        /** @var OrderedListenerProvider $provider */
        $provider = $this->container->get(OrderedListenerProvider::class);
        $finder = new ClassFinder();

        $listenerList = static function () use ($finder) {
            yield from $finder->find('../src/PageHandlers');
        };

        foreach ($listenerList() as $class) {
            // For the moment, only support class listeners and don't compile things.
            // We can optimize later with a compiled provider.
            $provider->listenerService($class);
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->container->get(StackMiddlewareKernel::class)->handle($request);
    }
}
