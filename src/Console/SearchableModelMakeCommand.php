<?php

declare(strict_types=1);

namespace ArcherZdip\ScoutElastic\Command;

use Psr\Container\ContainerInterface;
use Hyperf\Command\Annotation\Command;
use Hyperf\Database\Commands\ModelOption;
use Hyperf\Database\Commands\ModelCommand;

/**
 * @Command
 */
class SearchableModelMakeCommand extends ModelCommand
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->setName('make:searchable-model');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Create a new searchable model');
    }

    public function handle()
    {
        parent::handle();
    }

    /**
     * Build the class with the given name.
     */
    protected function buildClass(string $table, string $name, ModelOption $option): string
    {
        $stub = file_get_contents(__DIR__ . '/stubs/searchable_model.stub');

        return $this->replaceNamespace($stub, $name)
            ->replaceInheritance($stub, $option->getInheritance())
            ->replaceConnection($stub, $option->getPool())
            ->replaceUses($stub, $option->getUses())
            ->replaceClass($stub, $name)
            ->replaceTable($stub, $table);
    }
}
