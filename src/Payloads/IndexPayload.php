<?php


namespace ArcherZdip\ScoutElastic\Payloads;


use Exception;
use ArcherZdip\ScoutElastic\IndexConfigurator;

class IndexPayload extends RawPayload
{
    protected $protectedKeys = [
        'index'
    ];

    /**
     * The index configurator.
     *
     * @var IndexConfigurator
     */
    protected $indexConfigurator;

    /**
     * IndexPayload constructor.
     *
     * @param IndexConfigurator $indexConfigurator
     * @return void
     */
    public function __construct(IndexConfigurator $indexConfigurator)
    {
        $this->indexConfigurator = $indexConfigurator;
        // 设置索引名
        $this->payload['index'] = $indexConfigurator->getName();
    }

    /**
     * Use an alias.
     *
     * @param string $alias
     * @return $this
     * @throws \Exception
     */
    public function useAlias($alias)
    {
        $aliasGetter = 'get' . ucfirst($alias) . 'Alias';

        if (!method_exists($this->indexConfigurator, $aliasGetter)) {
            throw new Exception(sprintf(
                'The index configurator %s doesn\'t have getter for the %s alias.',
                get_class($this->indexConfigurator),
                $alias
            ));
        }

        $this->payload['index'] = call_user_func([$this->indexConfigurator, $aliasGetter]);

        return $this;
    }

    public function set($key, $value)
    {
        if (in_array($key, $this->protectedKeys)) {
            return $this;
        }

        return parent::set($key, $value);
    }

}