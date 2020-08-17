<?php


namespace ArcherZdip\ScoutElastic;


use stdClass;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Collection;
use Hyperf\Database\Model\Model;
use ArcherZdip\ScoutElastic\Builders\Builder;
use ArcherZdip\ScoutElastic\Traits\Migratable;
use ArcherZdip\ScoutElastic\Payloads\TypePayload;
use ArcherZdip\ScoutElastic\Builders\SearchBuilder;
use ArcherZdip\ScoutElastic\Indexers\IndexerInterface;


class ElasticEngine extends Engine
{

    /**
     * The indexer interface.
     *
     * @var IndexerInterface
     */
    protected $indexer;

    /**
     * Should the mapping be updated.
     *
     * @var bool
     */
    protected $updateMapping;

    /**
     * The updated mappings.
     *
     * @var array
     */
    protected static $updatedMappings = [];


    /**
     * ElasticEngine constructor.
     *
     * @param IndexerInterface $indexer
     * @param bool $updateMapping
     * @return void
     */
    public function __construct(IndexerInterface $indexer, $updateMapping)
    {
        $this->indexer = $indexer;


        $this->updateMapping = $updateMapping;
    }

    /**
     * Build the payload collection.
     *
     * @param Builder $builder
     * @param array $options
     * @return Collection|\Illuminate\Support\Collection
     * @throws \Exception
     */
    public function buildSearchQueryPayloadCollection(Builder $builder, array $options = [])
    {
        $payloadCollection = collect();

        if ($builder instanceof SearchBuilder) {
            $searchRules = $builder->rules ?: $builder->model->getSearchRules();

            foreach ($searchRules as $rule) {
                $payload = new TypePayload($builder->model);

                if (is_callable($rule)) {
                    $payload->setIfNotEmpty('body.query.bool', call_user_func($rule, $builder));
                } else {
                    /** @var SearchRule $ruleEntity */
                    $ruleEntity = new $rule($builder);

                    if ($ruleEntity->isApplicable()) {
                        $payload->setIfNotEmpty('body.query.bool', $ruleEntity->buildQueryPayload());

                        if ($options['highlight'] ?? true) {
                            $payload->setIfNotEmpty('body.highlight', $ruleEntity->buildHighlightPayload());
                        }
                    } else {
                        continue;
                    }
                }

                $payloadCollection->push($payload);
            }
        } else {
            $payload = (new TypePayload($builder->model))
                ->setIfNotEmpty('body.query.bool.must.match_all', new stdClass);

            $payloadCollection->push($payload);
        }

        return $payloadCollection->map(function (TypePayload $payload) use ($builder, $options) {
            $payload
                ->setIfNotEmpty('body._source', $builder->select)
                ->setIfNotEmpty('body.collapse.field', $builder->collapse)
                ->setIfNotEmpty('body.sort', $builder->orders)
                ->setIfNotEmpty('body.explain', $options['explain'] ?? null)
                ->setIfNotEmpty('body.profile', $options['profile'] ?? null)
                ->setIfNotEmpty('body.min_score', $builder->minScore)
                ->setIfNotNull('body.from', $builder->offset)
                ->setIfNotNull('body.size', $builder->limit);

            foreach ($builder->wheres as $clause => $filters) {
                $clauseKey = 'body.query.bool.filter.bool.' . $clause;

                $clauseValue = array_merge(
                    $payload->get($clauseKey, []),
                    $filters
                );

                $payload->setIfNotEmpty($clauseKey, $clauseValue);
            }
            return $payload->get();
        });
    }

    /**
     * Perform the search.
     *
     * @param Builder $builder
     * @param array $options
     * @return array|mixed
     * @throws \Exception
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        if ($builder->callback) {
            return call_user_func(
                $builder->callback,
                ElasticClient::getClient(),
                $builder->query,
                $options
            );
        }

        $results = [];

        $this
            ->buildSearchQueryPayloadCollection($builder, $options)
            ->each(function ($payload) use (&$results) {

                $results = ElasticClient::search($payload);

                $results['_payload'] = $payload;

                if ($this->getTotalCount($results) > 0) {
                    return false;
                }
            });

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $builder->from(($page - 1) * $perPage)->take($perPage);

        return $this->performSearch($builder);
    }

    /**
     * Explain the search.
     *
     * @param Builder $builder
     * @return array|mixed
     * @throws \Exception
     */
    public function explain(Builder $builder)
    {
        return $this->performSearch($builder, [
            'explain' => true,
        ]);
    }

    /**
     * Profile the search.
     *
     * @param Builder $builder
     * @return array|mixed
     * @throws \Exception
     */
    public function profile(Builder $builder)
    {
        return $this->performSearch($builder, [
            'profile' => true,
        ]);
    }

    /**
     * Return the number of documents found.
     *
     * @param Builder $builder
     * @return int
     * @throws \Exception
     */
    public function count(Builder $builder)
    {
        $count = 0;

        $this
            ->buildSearchQueryPayloadCollection($builder, ['highlight' => false])
            ->each(function ($payload) use (&$count) {
                $result = ElasticClient::count($payload);

                $count = $result['count'];

                if ($count > 0) {
                    return false;
                }
            });

        return $count;
    }

    /**
     * Make a raw search.
     *
     * @param Model $model
     * @param array $query
     * @return mixed
     * @throws \Exception
     */
    public function searchRaw(Model $model, $query)
    {
        $payload = (new TypePayload($model))
            ->setIfNotEmpty('body', $query)
            ->get();

        return ElasticClient::search($payload);
    }

    /**
     * {@inheritdoc}
     */
    public function mapIds($results)
    {
        return collect($results['hits']['hits'])->pluck('_id');
    }

    /**
     * @param Builder $builder
     * @param mixed $results
     * @param Model $model
     * @return \Hyperf\Database\Model\Collection|Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        if ($this->getTotalCount($results) === 0) {
            return Collection::make();
        }

        $scoutKeyName = $model->getScoutKeyName();

        $columns = Arr::get($results, '_payload.body._source');

        if (is_null($columns)) {
            $columns = ['*'];
        } else {
            $columns[] = $scoutKeyName;
        }

        $ids = $this->mapIds($results)->all();

        $query = $model->newQuery();

        $models = $query
            ->whereIn($scoutKeyName, $ids)
            ->get($columns)
            ->keyBy($scoutKeyName);

        return Collection::make($results['hits']['hits'])
            ->map(function ($hit) use ($models) {
                $id = $hit['_id'];

                if (isset($models[$id])) {
                    $model = $models[$id];

                    if (isset($hit['highlight'])) {
                        $model->highlight = new Highlight($hit['highlight']);
                    }

                    return $model;
                }
            })
            ->filter()
            ->values();
    }

    /**
     *
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['hits']['total']['value'];
    }

    /**
     * @inheritDoc
     */
    public function update($models)
    {
        if ($this->updateMapping) {
            $self = $this;

            $models->each(function ($model) use ($self) {
                $modelClass = get_class($model);

                if (in_array($modelClass, $self::$updatedMappings)) {
                    return true;
                }

                $this->updateMapping($model);

                $self::$updatedMappings[] = $modelClass;
            });
        }

        $this
            ->indexer
            ->update($models);
    }

    /**
     * 更新索引映射
     * @param $model
     * @throws \Exception
     */
    public static function updateMapping($model)
    {
        $configurator = $model->getIndexConfigurator();

        $mapping = array_merge_recursive(
            $configurator->getDefaultMapping(),
            $model->getMapping()
        );

        if (empty($mapping)) {
            throw new \LogicException('Nothing to update: the mapping is not specified.');
        }

        $payload = (new TypePayload($model))
            ->set('body.' . $model->searchableAs(), $mapping)
            ->set('include_type_name', 'true');

        if (in_array(Migratable::class, class_uses_recursive($configurator))) {
            $payload->useAlias('write');
        }

        ElasticClient::indices()->putMapping($payload->get());
    }

    /**
     * @inheritDoc
     */
    public function delete($models)
    {
        $this->indexer->delete($models);
    }

    /**
     * @inheritDoc
     */
    public function flush($model)
    {
        $query = $model::usesSoftDelete() ? $model->withTrashed() : $model->newQuery();

        $query
            ->orderBy($model->getScoutKeyName())
            ->unsearchable();
    }
}