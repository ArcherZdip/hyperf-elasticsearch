<?php

declare(strict_types=1);

namespace ArcherZdip\ScoutElastic\Command;

use Psr\Container\ContainerInterface;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use ArcherZdip\ScoutElastic\Console\Features\RequiresModelArgument;

/**
 * @Command
 */
class ElasticImportCommand extends HyperfCommand
{
    use RequiresModelArgument;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('elastic:import');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Import the given model into the search index');
    }

    public function handle()
    {
        $model = $this->getModel();

        $model::makeAllSearchable();

        $this->info('All [' . trim($this->input->getArgument('model')) . '] records have been imported.');
    }
}
