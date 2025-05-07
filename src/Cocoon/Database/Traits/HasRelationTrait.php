<?php
declare(strict_types=1);

namespace Cocoon\Database\Traits;

use Cocoon\Database\Relation\HasOne;
use Cocoon\Database\Relation\HasMany;
use Cocoon\Database\Relation\BelongsTo;
use Cocoon\Database\Relation\BelongsToMany;

trait HasRelationTrait
{

    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        return new HasOne($this, $related, $foreignKey, $localKey);
    }

    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        return new HasMany($this, $related, $foreignKey, $localKey);
    }

    public function belongsTo($related, $foreignKey = null, $localKey = null)
    {
        return new BelongsTo($this, $related, $foreignKey, $localKey);
    }

    public function belongsToMany($related, $refModel, $KeyOne = null, $keyTwo = null)
    {
        return new BelongsToMany($this, $related, $refModel, $KeyOne, $keyTwo);
    }

    public function loadRelationsByEagerLoading($params)
    {
        $collect = [];
        foreach ($params['with'] as $with) {
            if (isset($this->relations()[$with])) {
                $collect[$with] = $this->relations()[$with]->setEagerParams($params)->getCollection();
            }
        }
        return $collect;
    }

    public function getRelationByEagerLoading($collect, $withs, $entity)
    {
        foreach ($withs as $with) {
            if (isset($this->relations()[$with])) {
                $this->$with = $this->relations()[$with]->getByEagerloading($collect[$with], $entity);
            }
        }
    }
}
