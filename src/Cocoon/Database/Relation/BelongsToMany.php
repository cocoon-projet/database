<?php
declare(strict_types=1);

namespace Cocoon\Database\Relation;

use Cocoon\Collection\Collection;
use Cocoon\Database\Query\Builder;
use Cocoon\Utilities\Strings;

class BelongsToMany
{
    private $query;
    private $model;
    private $keyTwo;
    private $keyOne;
    private $refModel;
    private $related;
    private $eagerParams = [];
    private $keys = [];

    // TODO faire une fonction where
    public function __construct($model, $related, $refModel, $KeyOne, $keyTwo)
    {
        $this->query = Builder::init()->from($related::getTableName());
        $this->model = $model;
        $refModelTable = $refModel::getTableName();
        $this->related = $related;
        $this->refModel = $refModel;
        $this->keyOne = $refModelTable . '.' . Strings::singular($model::getTableName())
            . '_id' ?? $refModelTable . '.' . $KeyOne;
        $this->keyTwo = $refModelTable . '.' . Strings::singular($related::getTableName())
            .  '_id' ?? $refModelTable . '.' . $keyTwo;
    }

    protected function lazyLoadingConditions()
    {
        $relatedRefTable = $this->related::getTableName();
        $this->query->select($this->keyOne. ' AS ' . Strings::singular($this->model::getTableName())
            . '_id' . ',' . $relatedRefTable . '.*');
        $this->query->innerJoin($this->refModel::getTableName(), $this->keyTwo
            . ' = ' . $relatedRefTable . '.id');
        $id = $this->model->getPrimaryKey();
        $this->query->where($this->keyOne . ' = ?', $this->model->$id);
    }

    public function setEagerParams($params)
    {
        $this->eagerParams = $params;
        return $this;
    }

    protected function eagerLoadingConditions()
    {
        $relatedRefTable = $this->related::getTableName();
        $this->query->select($this->keyOne. ' AS '
            . Strings::singular($this->model::getTableName())
            . '_id' . ',' . $relatedRefTable . '.*');
        $this->query->innerJoin($this->refModel::getTableName(), $this->keyTwo
            . ' = ' . $relatedRefTable . '.id');
        $this->query->in($this->keyOne, $this->getKeys());
    }

    private function getKeys()
    {
        array_filter($this->eagerParams['results'], function ($getId) {
            $this->keys[] = $getId->id;
        });
        return array_unique($this->keys);
    }

    public function getCollection()
    {
        if (isset($this->eagerParams['with'])) {
            $this->eagerLoadingConditions();
            $result = $this->query->get();
        } else {
            $this->query->setModel($this->related);
            $this->lazyLoadingConditions();
            $result = collection($this->query->get());
        }
        //dd($result);
        return $result;
    }

    public function getByEagerloading($collection, $entity)
    {
        foreach ($collection as $collect) {
            $foreign = Strings::singular($this->model::getTableName()) . '_id';
            $id = $entity->getPrimaryKey();
            if ($collect->$foreign == $entity->$id) {
                $result[] = $this->hydrate($collect, $this->related);
            }
        }
        return new Collection($result);
    }

    protected function hydrate($data, $class)
    {
        $entity = new $class();
        foreach ($data as $key => $value) {
            $entity->$key = $value;
        }
        return $entity;
    }
}
