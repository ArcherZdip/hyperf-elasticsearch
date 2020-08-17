<?php


namespace ArcherZdip\ScoutElastic\Traits;

use Exception;
use Hyperf\Utils\Arr;
use ArcherZdip\ScoutElastic\Config;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\SoftDeletes;
use ArcherZdip\ScoutElastic\SearchRule;
use ArcherZdip\ScoutElastic\ElasticEngine;
use ArcherZdip\ScoutElastic\SearchableScope;
use ArcherZdip\ScoutElastic\IndexConfigurator;
use ArcherZdip\ScoutElastic\Builders\FilterBuilder;
use ArcherZdip\ScoutElastic\Builders\SearchBuilder;

trait Searchable
{
    /**
     * 元数据属性
     *
     * @var array
     */
    protected $scoutMetadata = [];

    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootSearchable()
    {
        static::addGlobalScope(new SearchableScope);

        (new static)->registerSearchableMacros();
    }

    /**
     * Register the searchable macros.
     *
     * @return void
     */
    public function registerSearchableMacros()
    {
        $self = $this;

        Collection::macro('searchable', function () use ($self) {
            $self->queueMakeSearchable($this);
        });

        Collection::macro('unsearchable', function () use ($self) {
            $self->queueRemoveFromSearch($this);
        });

    }

    /**
     * 获取所有搜索相关的元数据.
     *
     * @return array
     */
    public function scoutMetadata()
    {
        return $this->scoutMetadata;
    }

    /**
     * 使给定集合内的模型可搜索
     *
     * @param Collection $models
     * @return void
     */
    public function queueMakeSearchable($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $models->first()->searchableUsing()->update($models);

        printf('latest [%s] records have been imported' . PHP_EOL, $models->last()->getScoutKey());
    }

    /**
     * 使给定集合内的模型不可搜索
     *
     * @param Collection $models
     * @return void
     */
    public function queueRemoveFromSearch($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $models->first()->searchableUsing()->delete($models);
    }

    /**
     * es 搜索
     */
    public static function search($query, $callback = null)
    {
        $softDelete = static::usesSoftDelete() && config('scout_elastic.soft_delete', false);
        if ($query === '*') {
            return new FilterBuilder(new static, $callback, $softDelete);
        } else {
            return new SearchBuilder(new static, $query, $callback, $softDelete);
        }
    }

    /**
     * 执行原始搜索
     *
     * @param array $query
     * @return array
     * @throws Exception
     */
    public static function searchRaw(array $query)
    {
        $model = new static;

        return $model->searchableUsing()->searchRaw($model, $query);
    }

    /**
     * 搜索的索引类型（_type）
     * @return string
     */
    public function searchableAs()
    {
        return config('scout_elastic.prefix') . $this->getTable();
    }

    /**
     * 获取索引配置器
     *
     * @return IndexConfigurator
     * @throws Exception
     */
    public function getIndexConfigurator()
    {
        static $indexConfigurator;

        if (!$indexConfigurator) {
            if (!isset($this->indexConfigurator) || empty($this->indexConfigurator)) {
                throw new Exception(sprintf(
                    'An index configurator for the %s model is not specified.',
                    __CLASS__
                ));
            }

            $indexConfiguratorClass = $this->indexConfigurator;
            $indexConfigurator = new $indexConfiguratorClass;
        }

        return $indexConfigurator;
    }


    /**
     * 获取模型主键
     *
     * @return string
     */
    public function getScoutKeyName()
    {
        return $this->getKeyName();
    }

    /**
     * 模型主键
     * @return string|int
     */
    public function getScoutKey()
    {
        return $this->getkey();
    }

    /**
     * 将此模型的软删除状态同步到元数据中
     *
     * @return $this
     */
    public function pushSoftDeleteMetadata()
    {
        return $this->withScoutMetadata('__soft_deleted', $this->trashed() ? 1 : 0);
    }

    /**
     * 设置一个搜索相关的元数据
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function withScoutMetadata($key, $value)
    {
        $this->scoutMetadata[$key] = $value;

        return $this;
    }

    /**
     * Get the mapping.
     *
     * @return array
     */
    public function getMapping()
    {
        $mapping = $this->mapping ?? [];

        if ($this::usesSoftDelete() && config('scout_elastic.soft_delete', false)) {
            Arr::set($mapping, 'properties.__soft_deleted', ['type' => 'integer']);
        }

        return $mapping;
    }

    public function shouldBeSearchable()
    {
        return true;
    }

    /**
     * 获取模型的可索引数据数组
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return $this->toArray();
    }

    /**
     * 搜索规则
     * @return array
     */
    public function getSearchRules()
    {
        return isset($this->searchRules) && count($this->searchRules) > 0 ?
            $this->searchRules : [SearchRule::class];
    }

    /**
     * 使给定的模型实例可搜索。
     *
     * @return void
     */
    public function searchable()
    {
        $this->newCollection([$this])->searchable();
    }

    /**
     * 所有数据可搜索
     */
    public static function makeAllSearchable()
    {
        $self = new static;

        $softDelete = static::usesSoftDelete() && config('scout_elastic.soft_delete', false);

        $self->newQuery()
            ->when($softDelete, function ($query) {
                $query->withTrashed();
            })
            ->orderBy($self->getKeyName())
            ->searchable();
    }

    /**
     * 从搜索索引中删除给定的模型实例
     */
    public function unsearchable()
    {
        $this->newCollection([$this])->unsearchable();
    }

    /**
     * 删除所有搜索索引
     *
     * @return void
     */
    public static function removeAllFromSearch()
    {
        $self = new static;

        $self->searchableUsing()->flush($self);
    }

    /**
     * @return ElasticEngine
     */
    public function searchableUsing()
    {

        $config = ApplicationContext::getContainer()->get(Config::class);

        $indexerType = $config->indexer();

        $updateMapping = $config->updateMapping();

        $indexerClass = '\\ArcherZdip\ScoutElastic\\Indexers\\' . ucfirst($indexerType) . 'Indexer';

        return ApplicationContext::getContainer()->make(ElasticEngine::class, [
            'indexer' => make($indexerClass),
            'updateMapping' => $updateMapping
        ]);
    }


    /**
     * 确定当前类是否应该使用带搜索的软删除
     *
     * @return bool
     */
    protected static function usesSoftDelete()
    {
        return in_array(SoftDeletes::class, class_uses_recursive(get_called_class()));
    }

    //+----------------------
    //| 事件
    //+---------------------------------

    public function saved()
    {
        if (!$this->shouldBeSearchable()) {
            $this->unsearchable();

            return;
        }

        $this->searchable();
    }

    public function deleted()
    {
        if ($this->usesSoftDelete() && config('scout_elastic.soft_delete', false)) {
            $this->saved();
        } else {
            $this->unsearchable();
        }
    }

    public function forceDeleted()
    {
        $this->unsearchable();
    }

    public function restored()
    {
        $this->saved();
    }
}