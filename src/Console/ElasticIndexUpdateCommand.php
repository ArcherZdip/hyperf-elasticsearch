<?php

declare(strict_types=1);

namespace ArcherZdip\ScoutElastic\Console;

use Exception;
use LogicException;
use Psr\Container\ContainerInterface;
use Hyperf\Command\Annotation\Command;
use ArcherZdip\ScoutElastic\ElasticClient;
use Hyperf\Command\Command as HyperfCommand;
use ArcherZdip\ScoutElastic\Traits\Migratable;
use ArcherZdip\ScoutElastic\Payloads\RawPayload;
use ArcherZdip\ScoutElastic\Payloads\IndexPayload;
use ArcherZdip\ScoutElastic\Console\Features\RequiresIndexConfiguratorArgument;

/**
 * @Command
 */
class ElasticIndexUpdateCommand extends HyperfCommand
{
    use RequiresIndexConfiguratorArgument;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('elastic:update-index');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Update settings and mappings of an Elasticsearch index');
    }

    public function handle()
    {
        $this->updateIndex();

        $this->createWriteAlias();
    }

    /**
     * Update the index.
     *
     * @throws \Exception
     * @return void
     */
    protected function updateIndex()
    {
        $configurator = $this->getIndexConfigurator();

        $indexPayload = (new IndexPayload($configurator))->get();

        $indices = ElasticClient::indices();

        if (!$indices->exists($indexPayload)) {
            throw new LogicException(sprintf(
                'Index %s doesn\'t exist',
                $configurator->getName()
            ));
        }

        try {
            $indices->close($indexPayload);

            if ($settings = $configurator->getSettings()) {
                $indexSettingsPayload = (new IndexPayload($configurator))
                    ->set('body.settings', $settings)
                    ->get();

                $indices->putSettings($indexSettingsPayload);
            }

            $indices->open($indexPayload);
        } catch (Exception $exception) {
            $indices->open($indexPayload);

            throw $exception;
        }

        $this->info(sprintf(
            'The index %s was updated!',
            $configurator->getName()
        ));
    }

    /**
     * Create a write alias.
     *
     * @return void
     */
    protected function createWriteAlias()
    {
        $configurator = $this->getIndexConfigurator();

        if (!in_array(Migratable::class, class_uses_recursive($configurator))) {
            return;
        }

        $indices = ElasticClient::indices();

        $existsPayload = (new RawPayload())
            ->set('name', $configurator->getWriteAlias())
            ->get();

        if ($indices->existsAlias($existsPayload)) {
            return;
        }

        $putPayload = (new IndexPayload($configurator))
            ->set('name', $configurator->getWriteAlias())
            ->get();

        $indices->putAlias($putPayload);

        $this->info(sprintf(
            'The %s alias for the %s index was created!',
            $configurator->getWriteAlias(),
            $configurator->getName()
        ));
    }
}
