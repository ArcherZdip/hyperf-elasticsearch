<?php


namespace ArcherZdip\ScoutElastic;


use Swoole\Coroutine;
use Elasticsearch\ClientBuilder;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Guzzle\RingPHP\PoolHandler;
use Elasticsearch\Namespaces\IndicesNamespace;

/**
 * Class ElasticClient
 *
 * @method static array search($payload)
 * @method static array index($payload)
 * @method static array bulk($payload)
 * @method static array delete($payload)
 * @method static array info($payload)
 * @method static IndicesNamespace indices()
 */
class ElasticClient
{
    /**
     * @var Config
     */
    private static $config;

    public static function create()
    {
        self::$config = ApplicationContext::getContainer()->get(Config::class);

        $builder = ClientBuilder::create();
        if (Coroutine::getCid() > 0) {
            $handler = make(PoolHandler::class, [
                'option' => [
                    'max_connections' => self::$config->maxConnections() ?: 1,
                ],
            ]);
            $builder->setHandler($handler);
        }

        return $builder;
    }


    /**
     * @return \Elasticsearch\Client
     */
    public static function getClient()
    {
        /** @var \Elasticsearch\ClientBuilder $builder */
        return self::create()->setHosts(self::$config->host())->build();
    }


    public static function __callStatic($name, $arguments)
    {
        return static::getClient()->{$name}(...$arguments);
    }
}