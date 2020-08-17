<?php


namespace ArcherZdip\ScoutElastic\Console\Features;


use ArcherZdip\ScoutElastic\IndexConfigurator;
use Symfony\Component\Console\Input\InputArgument;

trait RequiresIndexConfiguratorArgument
{
    /**
     * Get the index configurator.
     *
     * @return IndexConfigurator
     */
    protected function getIndexConfigurator()
    {
        $configuratorClass = trim($this->input->getArgument('index-configurator'));

        $configuratorInstance = new $configuratorClass;

        if (!($configuratorInstance instanceof IndexConfigurator)) {
            throw new \InvalidArgumentException(sprintf(
                'The class %s must extend %s.',
                $configuratorClass,
                IndexConfigurator::class
            ));
        }

        return new $configuratorClass;
    }

    /**
     * Get the arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            [
                'index-configurator',
                InputArgument::REQUIRED,
                'The index configurator class',
            ],
        ];
    }
}