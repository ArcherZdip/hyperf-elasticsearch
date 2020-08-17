<?php

namespace ArcherZdip\ScoutElastic\Traits;

trait Migratable
{
    /**
     * 获取写别名
     *
     * @return string
     */
    public function getWriteAlias()
    {
        return $this->getName() . '_write';
    }
}