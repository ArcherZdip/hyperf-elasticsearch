<?php


namespace ArcherZdip\ScoutElastic\Console\Features;


use ArcherZdip\ScoutElastic\Traits\Searchable;
use InvalidArgumentException;
use Hyperf\Database\Model\Model;
use Symfony\Component\Console\Input\InputArgument;

trait RequiresModelArgument
{
    /**
     * Get the model.
     *
     * @return Model
     */
    protected function getModel()
    {
        $modelClass = trim($this->input->getArgument('model'));

        $modelInstance = new $modelClass;

        if (
            !($modelInstance instanceof Model) ||
            !in_array(Searchable::class, class_uses_recursive($modelClass))
        ) {
            throw new InvalidArgumentException(sprintf(
                'The %s class must extend %s and use the %s trait.',
                $modelClass,
                Model::class,
                Searchable::class
            ));
        }

        return $modelInstance;
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
                'model',
                InputArgument::REQUIRED,
                'The model class',
            ],
        ];
    }
}