<?php

declare(strict_types=1);

namespace Crell\MiDy;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\FuncAnalyzer;
use Crell\AttributeUtils\FunctionAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\AttributeUtils\MemoryCacheFunctionAnalyzer;
use Crell\Carica\Middleware\AdditionalMiddlewareMiddleware;
use Crell\Carica\Middleware\CacheHeaderMiddleware;
use Crell\Carica\Middleware\DefaultContentTypeMiddleware;
use Crell\Carica\Middleware\DeriveActionMetadataMiddleware;
use Crell\Carica\Middleware\EnforceHeadMiddleware;
use Crell\Carica\Middleware\ExceptionCatcherMiddleware;
use Crell\Carica\Middleware\GenericMethodNotAllowedMiddleware;
use Crell\Carica\Middleware\GenericNotFoundMiddleware;
use Crell\Carica\Middleware\NormalizeArgumentTypesMiddleware;
use Crell\Carica\Middleware\ParsedBodyMiddleware;
use Crell\Carica\ResponseBuilder as CaricaResponseBuilder;
use Crell\Carica\Router\ActionDispatcher;
use Crell\Carica\Router\DelegatingRouter;
use Crell\Carica\Router\Router;
use Crell\Carica\Router\RouterMiddleware;
use Crell\Carica\SerdeBodyParser;
use Crell\Carica\StackMiddlewareKernel;
use Crell\Config\ConfigLoader;
use Crell\Config\IniFileSource;
use Crell\Config\LayeredLoader;
use Crell\Config\PhpFileSource;
use Crell\Config\SerializedFilesystemCache;
use Crell\MiDy\LatteTheme\LatteThemeExtension;
use Crell\MiDy\MarkdownDeserializer\MarkdownPageLoader;
use Crell\MiDy\MarkdownLatte\CommonMarkExtension;
use Crell\MiDy\PageTree\Latte\PageTreeExtension;
use Crell\MiDy\PageTree\PageCache;
use Crell\MiDy\PageTree\PageTree;
use Crell\MiDy\PageTree\Parser\HtmlFileParser;
use Crell\MiDy\PageTree\Parser\LatteFileParser;
use Crell\MiDy\PageTree\Parser\MarkdownLatteFileParser;
use Crell\MiDy\PageTree\Parser\MultiplexedFileParser;
use Crell\MiDy\PageTree\Parser\Parser;
use Crell\MiDy\PageTree\Parser\PhpFileParser;
use Crell\MiDy\PageTree\Parser\StaticFileParser;
use Crell\MiDy\PageTree\Router\LatteHandler;
use Crell\MiDy\PageTree\Router\MarkdownLatteHandler;
use Crell\MiDy\PageTree\Router\NotFoundErrorHandler;
use Crell\MiDy\PageTree\Router\PageTreeRouter;
use Crell\MiDy\PageTree\Router\PhpHandler;
use Crell\MiDy\PageTree\Router\StaticFileHandler;
use Crell\MiDy\PageTree\YiiDbPageCache;
use Crell\MiDy\Router\EventRouter\PageHandlerListeners\MarkdownLatteHandlerListener;
use Crell\MiDy\Services\PrintLogger;
use Crell\MiDy\Services\ResponseBuilder;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;
use Crell\Tukio\DebugEventDispatcher;
use Crell\Tukio\Dispatcher;
use Crell\Tukio\OrderedListenerProvider;
use DI\ContainerBuilder;
use HttpSoft\Emitter\SapiEmitter;
use Latte\Engine;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Environment\EnvironmentInterface;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use PDO;
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
use Psr\SimpleCache\CacheInterface;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;
use Spatie\CommonMarkHighlighter\IndentedCodeRenderer;
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Driver\Pdo\PdoDriverInterface;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;

use function DI\autowire;
use function DI\env;
use function DI\factory;
use function DI\get;
use function DI\value;

class MiDy implements RequestHandlerInterface
{
    public readonly ContainerInterface $container;

    private readonly string $appRoot;

    private readonly string $routePath;
    private readonly string $cachePath;
    private readonly string $configPath;
    private readonly string $templatesPath;
    private readonly string $publicPath;

    private readonly string $midyPath;

    /**
     * @param string $appRoot
     *   The source root of the application. The default assumes the running
     *   script is one level down from the source root, in a public folder.
     * @param string|null $routesPath
     *   The path to the routes folder.
     * @param string|null $cachePath
     *   The root of the cache folder.
     * @param string|null $configPath
     *   The root of the configuration folder.
     * @param string|null $templatesPath
     *   The root of the templates folder.
     * @param string|null $publicPath
     *   The root of the public (web-accessible) folder.
     */
    public function __construct(
        string $appRoot = '..',
        ?string $routesPath = null,
        ?string $cachePath = null,
        ?string $configPath = null,
        ?string $templatesPath = null,
        ?string $publicPath = null,
    ) {
        $this->appRoot = realpath($appRoot) ?? '';

        $this->cachePath = $this->ensurePath($cachePath, $_ENV['CACHE_PATH'] ?? '/cache');
        $this->routePath = $this->ensurePath($routesPath, $_ENV['ROUTES_PATH'] ?? '/routes');
        $this->configPath = $this->ensurePath($configPath, $_ENV['CONFIG_PATH'] ?? '/configuration');
        $this->templatesPath = $this->ensurePath($templatesPath, $_ENV['TEMPLATES_PATH'] ?? '/templates');
        $this->publicPath = $this->ensurePath($publicPath, $_ENV['PUBLIC_PATH'] ?? '/public');

        $this->midyPath = $this->ensurePath(dirname(__FILE__) . '/..', '');

        $this->container = $this->buildContainer();
        $this->setupListeners();
    }

    protected function ensurePath(?string $override, string $default): string
    {
        $dir = $override ?? ($this->appRoot . '/' . trim($default, '/'));

        return ensure_dir($dir);
    }

    protected function buildContainer(): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAttributes(true);

        $finder = new ClassFinder();

        $codePaths = [
            $this->midyPath . '/src/',
        ];

        foreach ($codePaths as $path) {
            foreach ($finder->find($path) as $class) {
                $containerBuilder->addDefinitions([
                    $class => autowire(),
                ]);
            }
        }

        $containerBuilder->addDefinitions([
            // User-defined paths.
            'paths.routes' => value($this->routePath),
            'paths.config' => value($this->configPath),
            'paths.cache' => value($this->cachePath),
            'paths.templates' => value($this->templatesPath),
            'paths.public' => value($this->publicPath),
            'paths.app.root' => value($this->appRoot),

            // Derived paths.
            'paths.cache.routes' => value(ensure_dir($this->cachePath . '/routes')),
            'paths.cache.config' => value(ensure_dir($this->cachePath . '/config')),
            'paths.cache.latte' => value(ensure_dir($this->cachePath . '/latte')),
            'paths.cache.yii' => value(ensure_dir($this->cachePath . '/yii')),
            'path.cache.routes.dsn' => value('sqlite:' . $this->cachePath . '/routes/routes.sq3'),
            'path.cache.routes.dbname' => value($this->cachePath . '/routes/routes.sq3'),
        ]);

        // General utilities
        $containerBuilder->addDefinitions([
            ClassFinder::class => autowire(),
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

        $containerBuilder->addDefinitions([
            // Register the middleware first.
            AdditionalMiddlewareMiddleware::class => autowire(),
            CacheHeaderMiddleware::class => autowire(),
            DefaultContentTypeMiddleware::class => autowire(),
            EnforceHeadMiddleware::class => autowire(),
            DeriveActionMetadataMiddleware::class => autowire(),
            ExceptionCatcherMiddleware::class => autowire()
                ->constructorParameter('debug', true)
            ,
            GenericMethodNotAllowedMiddleware::class => autowire(),
            GenericNotFoundMiddleware::class => autowire(),
            NormalizeArgumentTypesMiddleware::class => autowire(),
            ParsedBodyMiddleware::class => autowire()
                ->constructorParameter('parsers', [get(SerdeBodyParser::class)])
            ,
            RouterMiddleware::class => autowire()
                ->constructorParameter('notFoundHandler', get(NotFoundErrorHandler::class))
            ,

            // And the base dispatcher.
            ActionDispatcher::class => autowire(),

            // Error handlers.
            NotFoundErrorHandler::class => autowire(),

            // Body parser.
            SerdeBodyParser::class => autowire(),

            PageTreeRouter::class => autowire(),

            Router::class => get(PageTreeRouter::class),

            // The kernel.
            StackMiddlewareKernel::class => autowire()
                ->constructorParameter('baseHandler', get(ActionDispatcher::class))
                ->constructorParameter('middleware', [
                    get(ExceptionCatcherMiddleware::class),
                    get(DefaultContentTypeMiddleware::class),
                    get(CacheHeaderMiddleware::class),
                    get(EnforceHeadMiddleware::class),
                    get(RouterMiddleware::class),
                    get(GenericNotFoundMiddleware::class),
                    get(GenericMethodNotAllowedMiddleware::class),
                    get(DeriveActionMetadataMiddleware::class),
                    get(NormalizeArgumentTypesMiddleware::class),
                    get(ParsedBodyMiddleware::class),
                ])
            ,

            SapiEmitter::class => autowire(SapiEmitter::class),
        ]);

        $containerBuilder->addDefinitions([
            PageCache::class => get(YiiDbPageCache::class),
        ]);

        // Routing
        $containerBuilder->addDefinitions([
            DelegatingRouter::class => autowire()
                ->constructorParameter('default', get(PageTreeRouter::class))
            ,
            Router::class => get(DelegatingRouter::class),

            CacheInterface::class => autowire(FileCache::class)
                ->constructor(get('paths.cache.yii'))
            ,
            SchemaCache::class => autowire(),
            PdoDriverInterface::class => autowire(Driver::class)
                ->constructorParameter('dsn', get('path.cache.routes.dsn'))
                ->constructorParameter('attributes', [PDO::ATTR_STRINGIFY_FETCHES => false])
            ,
            Connection::class => autowire(),

            Parser::class => autowire()
                ->constructorParameter('fileParser', get(MultiplexedFileParser::class))
            ,
            MultiplexedFileParser::class => autowire()
                ->method('addParser', get(HtmlFileParser::class))
                ->method('addParser', get(StaticFileParser::class))
                ->method('addParser', get(LatteFileParser::class))
                ->method('addParser', get(MarkdownLatteFileParser::class))
                ->method('addParser', get(PhpFileParser::class))
            ,
            PageTreeRouter::class => autowire()
                ->method('addHandler', get(StaticFileHandler::class))
                ->method('addHandler', get(LatteHandler::class))
                ->method('addHandler', get(MarkdownLatteHandler::class))
                ->method('addHandler', get(PhpHandler::class))
            ,
            MarkdownLatteHandler::class => autowire()
            ,
            PageTree::class => autowire()
                ->constructorParameter('rootPhysicalPath', get('paths.routes'))
            ,
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
            ConsoleLogger::class => autowire(),
            LoggerInterface::class => get(NullLogger::class),
        ]);

        // HTTP handling.
        $containerBuilder->addDefinitions([
            ResponseFactoryInterface::class => get(Psr17Factory::class),
            StreamFactoryInterface::class => get(Psr17Factory::class),
            RequestFactoryInterface::class => get(Psr17Factory::class),
            ServerRequestFactoryInterface::class => get(Psr17Factory::class),
            UriFactoryInterface::class => get(Psr17Factory::class),

            ServerRequestCreatorInterface::class => get(ServerRequestCreator::class),
            ServerRequestCreator::class => autowire()
                ->constructor(
                    serverRequestFactory: get(ServerRequestFactoryInterface::class),
                    uriFactory: get(Psr17Factory::class),
                    uploadedFileFactory: get(Psr17Factory::class),
                    streamFactory: get(StreamFactoryInterface::class),
                ),
            // Just temporarily, until the caching is moved elsewhere.
            ResponseBuilder::class => autowire()
                ->constructorParameter('enableCache', env('ENABLE_CACHE', true))
            ,
            CaricaResponseBuilder::class => get(ResponseBuilder::class),
        ]);

        // Commonmark
        // @todo Configure this better so it's configurable somehow?
        $containerBuilder->addDefinitions([
            CommonMarkCoreExtension::class => autowire(),
            GithubFlavoredMarkdownExtension::class => autowire(),
            FencedCodeRenderer::class => autowire(),
            IndentedCodeRenderer::class => autowire(),
            EnvironmentInterface::class => autowire(Environment::class)
                ->method('addExtension', get(CommonMarkCoreExtension::class))
                ->method('addExtension', get(GithubFlavoredMarkdownExtension::class))
                ->method('addRenderer', FencedCode::class, get(FencedCodeRenderer::class))
                ->method('addRenderer', IndentedCode::class, get(IndentedCodeRenderer::class))
            ,
            MarkdownConverter::class => autowire(),
            ConverterInterface::class => get(MarkdownConverter::class),
        ]);

        // My Latte/Commonmark extension.
        $containerBuilder->addDefinitions([
            CommonMarkExtension::class => autowire(),
        ]);

        // MarkdownLatteHandler related stuff
        $containerBuilder->addDefinitions([
            MarkdownLatteHandlerListener::class => autowire()
                ->constructorParameter('templateRoot', get('paths.templates')),
            // Because the file name it gets passed will already be absolute.
            MarkdownPageLoader::class => autowire()
                ->constructorParameter('root', '/')
        ]);

        // Latte templates
        $containerBuilder->addDefinitions([
            Engine::class => autowire()
                ->method('addExtension', get(CommonMarkExtension::class))
                ->method('addExtension', get(PageTreeExtension::class))
                ->method('addExtension', get(LatteThemeExtension::class))
                ->method('setTempDirectory', get('paths.cache.latte'))
            ,
            PageTreeExtension::class => autowire()
                ->constructorParameter('baseUrl', env('BASE_URL', 'http://localhost/'))
            ,
            LatteThemeExtension::class => autowire()
                ->constructorParameter('allowedRoot', value($this->appRoot))
                ->constructorParameter('core', value($this->midyPath . '/src/templates'))
                ->constructorParameter('site', get('paths.templates'))
            ,
        ]);

        $configPaths = [
            $this->midyPath . '/src/Config',
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
        // Refactoring has removed all the built in listeners, surprisingly.
        // @todo come back to this if that changes, or remove this method.
        return;


        /** @var OrderedListenerProvider $provider */
        $provider = $this->container->get(OrderedListenerProvider::class);
        $finder = new ClassFinder();

        $listenerList = function () use ($finder) {
            //yield from $finder->find($this->appRoot . '/src/Router\EventRouter\PageHandlerListeners');
//            yield from $finder->find($this->appRoot . '/src/Listeners');
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
