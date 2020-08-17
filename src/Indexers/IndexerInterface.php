<?php

namespace ArcherZdip\ScoutElastic\Indexers;


use Hyperf\Database\Model\Collection;

interface IndexerInterface
{
    /**
     * Update documents.
     *
     * @param Collection $models
     * @return array
     */
    public function update(Collection $models);

    /**
     * Delete documents.
     *
     * @param Collection $models
     * @return array
     */
    public function delete(Collection $models);
}