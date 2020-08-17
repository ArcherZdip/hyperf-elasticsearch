<?php


namespace ArcherZdip\ScoutElastic;

use Hyperf\Utils\Arr;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Scope;
use Hyperf\Database\Model\Builder;
use Hyperf\Utils\ApplicationContext;

class SearchableScope implements Scope
{
    /**
     * @inheritDoc
     */
    public function apply(Builder $builder, Model $model)
    {
        // TODO: Implement apply() method.
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param Builder $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        $config = ApplicationContext::getContainer()->get(Config::class);

        $builder->macro('searchable', function (Builder $builder, $chunk = null) use ($config) {
            $builder->chunk($chunk ?: Arr::get($config->chunk(), 'searchable', 500), function ($models) {
                $models->filter->shouldBeSearchable()->searchable();
            });
        });

        $builder->macro('unsearchable', function (Builder $builder, $chunk = null) use ($config) {
            $builder->chunk($chunk ?: Arr::get($config->chunk(), 'unsearchable', 500), function ($models) {
                $models->unsearchable();
            });
        });
    }
}