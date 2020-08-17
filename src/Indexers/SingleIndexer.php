<?php


namespace ArcherZdip\ScoutElastic\Indexers;


use ArcherZdip\ScoutElastic\Config;
use ArcherZdip\ScoutElastic\ElasticClient;
use ArcherZdip\ScoutElastic\Payloads\DocumentPayload;
use ArcherZdip\ScoutElastic\Traits\Migratable;
use Hyperf\Database\Model\Collection;

class SingleIndexer implements IndexerInterface
{

    /**
     * @var Config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function update(Collection $models)
    {
        $models->each(function ($model) {
            if ($model::usesSoftDelete() && config('scout_elasticsearch.soft_delete', false)) {
                $model->pushSoftDeleteMetadata();
            }

            $modelData = array_merge(
                $model->toSearchableArray(),
                $model->scoutMetadata()
            );

            if (empty($modelData)) {
                return true;
            }

            $indexConfigurator = $model->getIndexConfigurator();

            $payload = (new DocumentPayload($model))
                ->set('body', $modelData);

            if (in_array(Migratable::class, class_uses_recursive($indexConfigurator))) {
                $payload->useAlias('write');
            }

            if ($documentRefresh = $this->config->documentRefresh()) {
                $payload->set('refresh', $documentRefresh);
            }

            ElasticClient::index($payload->get());
        });
    }

    /**
     * @inheritDoc
     */
    public function delete(Collection $models)
    {
        $models->each(function ($model) {
            $payload = new DocumentPayload($model);

            if ($documentRefresh = $this->config->documentRefresh()) {
                $payload->set('refresh', $documentRefresh);
            }

            $payload->set('client.ignore', 404);

            ElasticClient::delete($payload->get());
        });
    }
}