<?php

declare(strict_types=1);

namespace Crell\MiDy;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\FuncAnalyzer;
use Crell\AttributeUtils\FunctionAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\AttributeUtils\MemoryCacheFunctionAnalyzer;
use Crell\Config\ConfigLoader;
use Crell\Config\IniFileSource;
use Crell\Config\LayeredLoader;
use Crell\Config\PhpFileSource;
use Crell\Config\SerializedFilesystemCache;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\MarkdownLatte\CommonMarkExtension;
use Crell\MiDy\Middleware\CacheMiddleware;
use Crell\MiDy\Middleware\DeriveFormatMiddleware;
use Crell\MiDy\Middleware\EnforceHeadMiddleware;
use Crell\MiDy\Middleware\LogMiddleware;
use Crell\MiDy\Middleware\ParamConverterMiddleware;
use Crell\MiDy\Middleware\RoutingMiddleware;
use Crell\MiDy\PageHandlerListeners\MarkdownLatteHandler;
use Crell\MiDy\Router\DelegatingRouter;
use Crell\MiDy\Router\EventRouter;
use Crell\MiDy\Router\MappedRouter;
use Crell\MiDy\Router\Router;
use Crell\MiDy\Services\ActionInvoker;
use Crell\MiDy\Services\PrintLogger;
use Crell\MiDy\Services\RuntimeActionInvoker;
use Crell\MiDy\Middleware\RequestPathMiddleware;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;
use Crell\Tukio\DebugEventDispatcher;
use Crell\Tukio\Dispatcher;
use Crell\Tukio\OrderedListenerProvider;
use DI\ContainerBuilder;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Latte\Engine;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\ConverterInterface;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use League\CommonMark\MarkdownConverter;
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
use function DI\factory;
use function DI\get;
use function DI\value;

class MiDy implements RequestHandlerInterface
{
    public readonly ContainerInterface $container;

    public function __construct(private readonly string $appRoot = '..')
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
            $this->appRoot . '/src/',
        ];

        foreach ($codePaths as $path) {
            foreach ($finder->find($path) as $class) {
                $containerBuilder->addDefinitions([
                    $class => autowire(),
                ]);
            }
        }

        $containerBuilder->addDefinitions([
            // Paths.  Todo: Make this configurable.
            'paths.routes' => value(\realpath($this->appRoot . '/routes')),
            'paths.config' => value(\realpath($this->appRoot . '/configuration')),
            'paths.cache' => value(\realpath($this->appRoot . '/cache')),
            'paths.cache.config' => value(\realpath($this->appRoot . '/cache/config')),
            'paths.cache.latte' => value(\realpath($this->appRoot . '/cache/latte')),
            'paths.templates' => value(\realpath($this->appRoot . '/templates')),
        ]);

        // Configuration
        $containerBuilder->addDefinitions([
            IniFileSource::class => autowire()->constructorParameter('directory', get('paths.config')),
            PhpFileSource::class => autowire()->constructorParameter('directory', get('paths.config')),
            LayeredLoader::class => autowire()->constructorParameter('sources', [get(IniFileSource::class), get(PhpFileSource::class)]),
            SerializedFilesystemCache::class => autowire()
                ->constructorParameter('loader', get(LayeredLoader::class))
                ->constructorParameter('directory', get('paths.cache.config'))
            ,
            ConfigLoader::class => get(SerializedFilesystemCache::class),
        ]);

        // Core middleware and execution pipeline.
        $containerBuilder->addDefinitions([
            StackMiddlewareKernel::class => autowire(StackMiddlewareKernel::class)
                ->constructor(baseHandler: get(ActionRunner::class))
                // These will run last to first, ie, the earlier listed ones are "more inner."
                // That makes interlacing request, response, and "both" middlewares tricky.
                ->method('addMiddleware', get(ParamConverterMiddleware::class))
//        ->method('addMiddleware', get(AuthorizationMiddleware::class))
                ->method('addMiddleware', get(RoutingMiddleware::class))
                ->method('addMiddleware', get(RequestPathMiddleware::class))
                ->method('addMiddleware', get(DeriveFormatMiddleware::class))
//        ->method('addMiddleware', get(AuthenticationMiddleware::class))
                ->method('addMiddleware', get(CacheMiddleware::class))
                ->method('addMiddleware', get(EnforceHeadMiddleware::class))
                ->method('addMiddleware', get(LogMiddleware::class))
            ,
            SapiEmitter::class => autowire(SapiEmitter::class)
            ,
            EventRouter::class => autowire()->constructorParameter('routesPath', get('paths.routes')),
            ActionInvoker::class => get(RuntimeActionInvoker::class)
        ]);

        // Tukio Event Dispatcher
        $containerBuilder->addDefinitions([
            Dispatcher::class => autowire(),
            DebugEventDispatcher::class => autowire()
                ->constructorParameter('dispatcher', get(Dispatcher::class)),
            OrderedListenerProvider::class => autowire()
                ->constructorParameter('container', get(ContainerInterface::class)),
            ListenerProviderInterface::class => get(OrderedListenerProvider::class),
            EventDispatcherInterface::class => get(Dispatcher::class),

        ]);

        // AttributeUtils
        // Serde
        $containerBuilder->addDefinitions([
            ClassAnalyzer::class => autowire(),
            FuncAnalyzer::class => autowire(),
            MemoryCacheAnalyzer::class => autowire()->constructorParameter('analyzer', get(ClassAnalyzer::class)),
            MemoryCacheFunctionAnalyzer::class => autowire()->constructorParameter('analyzer', get(FuncAnalyzer::class)),
            Analyzer::class => get(MemoryCacheAnalyzer::class),
            FunctionAnalyzer::class => get(FuncAnalyzer::class),

            SerdeCommon::class => autowire(),
            Serde::class => get(SerdeCommon::class),
        ]);

        // Logging
        $containerBuilder->addDefinitions([
            NullLogger::class => autowire(),
            PrintLogger::class => autowire(),
            LoggerInterface::class => get(NullLogger::class),
        ]);

        // HTTP handling.
        $containerBuilder->addDefinitions([
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
        ]);

        // Commonmark
        // @todo Configure this better so it's configurable somehow?
        $containerBuilder->addDefinitions([
            CommonMarkConverter::class => autowire(),
            GithubFlavoredMarkdownConverter::class => autowire(),
            ConverterInterface::class => get(GithubFlavoredMarkdownConverter::class),
            MarkdownConverter::class =>get(GithubFlavoredMarkdownConverter::class),
        ]);

        $containerBuilder->addDefinitions([
            CommonMarkExtension::class => autowire(),
        ]);

        // MarkdownLatteHandler related stuff
        $containerBuilder->addDefinitions([
            MarkdownLatteHandler::class => autowire()
                ->constructorParameter('templateRoot', get('paths.templates')),
            // Because the file name it gets passed will already be absolute.
            MarkdownPageLoader::class => autowire()
                ->constructorParameter('root', '/')
        ]);

        // Latte templates
        $containerBuilder->addDefinitions([
            Engine::class => autowire()
                ->method('addExtension', get(CommonMarkExtension::class))
                ->method('setTempDirectory', get('paths.cache.latte')),
        ]);

        $configPaths = [
            $this->appRoot . '/src/Config',
        ];

        foreach ($configPaths as $path) {
            foreach ($finder->find($path) as $class) {
                $containerBuilder->addDefinitions([
                    $class => factory(fn(ContainerInterface $c) => $c->get(ConfigLoader::class)->load($class)),
                ]);
            }
        }

        $containerBuilder->addDefinitions([]);

        return $containerBuilder->build();
    }

    public function setupListeners(): void
    {
        /** @var OrderedListenerProvider $provider */
        $provider = $this->container->get(OrderedListenerProvider::class);
        $finder = new ClassFinder();

        $listenerList = function () use ($finder) {
            yield from $finder->find($this->appRoot . '/src/PageHandlerListeners');
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
