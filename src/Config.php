<?php


namespace ArcherZdip\ScoutElastic;

use Hyperf\Utils\Arr;

/**
 * Class Config
 * @package ArcherZdip\ScoutElastic
 */
class Config
{
    public function driver()
    {
        return Arr::get($this->getConfig(), 'driver', 'default');
    }

    /**
     * 最大连接数
     * @return int
     */
    public function maxConnections(): int
    {
        return $this->getValue('max_connections');
    }

    public function host(): array
    {
        return $this->getValue('client.hosts');
    }

    public function indexer(): string
    {
        return $this->getValue('indexer');
    }

    public function queue(): bool
    {
        return $this->getValue('queue');
    }

    public function chunk(): array
    {
        return $this->getValue('chunk');
    }

    public function updateMapping(): bool
    {
        return $this->getValue('update_mapping');
    }

    public function documentRefresh()
    {
        return $this->getValue('document_refresh');
    }

    protected function getConfig()
    {
        return config('scout_elastic');
    }

    protected function getValue($key)
    {
        return Arr::get($this->getConfig(), $this->driver() . '.' . $key);
    }
}