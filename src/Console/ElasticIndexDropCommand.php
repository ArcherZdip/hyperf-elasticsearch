<?php

declare(strict_types=1);

namespace ArcherZdip\ScoutElastic\Command;

use Psr\Container\ContainerInterface;
use Hyperf\Command\Annotation\Command;
use ArcherZdip\ScoutElastic\ElasticClient;
use Hyperf\Command\Command as HyperfCommand;
use ArcherZdip\ScoutElastic\IndexConfigurator;
use ArcherZdip\ScoutElastic\Traits\Migratable;
use ArcherZdip\ScoutElastic\Payloads\RawPayload;
use ArcherZdip\ScoutElastic\Console\Features\RequiresIndexConfiguratorArgument;

/**
 * @Command
 */
class ElasticIndexDropCommand extends HyperfCommand
{
    use RequiresIndexConfiguratorArgument;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('elastic:drop-index');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Drop an Elasticsearch index');
    }

    public function handle()
    {
        $configurator = $this->getIndexConfigurator();
        $indexName = $this->resolveIndexName($configurator);

        $payload = (new RawPayload())
            ->set('index', $indexName)
            ->get();

        ElasticClient::indices()
            ->delete($payload);

        $this->info(sprintf(
            'The index %s was deleted!',
            $indexName
        ));
    }

    /**
     * @param IndexConfigurator $configurator
     * @return string
     */
    protected function resolveIndexName($configurator)
    {
        if (in_array(Migratable::class, class_uses_recursive($configurator))) {
            $payload = (new RawPayload())
                ->set('name', $configurator->getWriteAlias())
                ->get();

            $aliases = ElasticClient::indices()
                ->getAlias($payload);

            return key($aliases);
        } else {
            return $configurator->getName();
        }
    }
}
