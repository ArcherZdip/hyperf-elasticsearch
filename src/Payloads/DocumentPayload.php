<?php


namespace ArcherZdip\ScoutElastic\Payloads;


use Exception;
use Hyperf\Database\Model\Model;

class DocumentPayload extends TypePayload
{
    public function __construct(Model $model)
    {
        if ($model->getScoutKey() === null) {
            throw new Exception(sprintf(
                'The key value must be set to construct a payload for the %s instance.',
                get_class($model)
            ));
        }

        parent::__construct($model);

        $this->payload['id'] = $model->getScoutKey();
        $this->protectedKeys[] = 'id';
    }
}