<?php

declare(strict_types=1);

namespace ArcherZdip\ScoutElastic\Command;

use Psr\Container\ContainerInterface;
use Hyperf\Command\Annotation\Command;
use Hyperf\Devtool\Generator\GeneratorCommand;

/**
 * @Command
 */
class IndexConfiguratorMakeCommand extends GeneratorCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('make:index-configurator');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Create a new Elasticsearch index configurator');
    }

    protected function getStub(): string
    {
        return __DIR__.'/stubs/index_configurator.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return $this->getConfig()['namespace'] ?? 'App\\ElasticIndexConfigurator';
    }
}
