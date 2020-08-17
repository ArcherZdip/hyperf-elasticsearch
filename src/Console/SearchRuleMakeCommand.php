<?php

declare(strict_types=1);

namespace ArcherZdip\ScoutElastic\Command;

use Psr\Container\ContainerInterface;
use Hyperf\Command\Annotation\Command;
use Hyperf\Devtool\Generator\GeneratorCommand;

/**
 * @Command
 */
class SearchRuleMakeCommand extends GeneratorCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('make:search-rule');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Create a new search rule');
    }


    protected function getStub(): string
    {
        return __DIR__.'/stubs/search_rule.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return $this->getConfig()['namespace'] ?? 'App\\ElasticSearchRule';
    }
}
