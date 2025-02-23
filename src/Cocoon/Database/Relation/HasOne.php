<?php
declare(strict_types=1);

namespace Cocoon\Database\Relation;

use Cocoon\Database\Query\Builder;
use Cocoon\Utilities\Strings;

class HasOne
{
    private $query;
    private $related;
    private $foreignKey;
    private $localKey;
    private $model;
    private $eagerParams = [];
    private $keys = [];
    // TODO faire une fonction where
    public function __construct($model, $related, $foreignKey, $localKey)
    {
        $this->query = Builder::init()->from($related::getTableName());
        $this->model = $model;
        $this->related = $related;
        $this->foreignKey = $foreignKey ?? Strings::singular($model::getTableName()) . '_id';
        $this->localKey = $localKey ?? $model->getPrimaryKey();
    }

    protected function lazyLoadingConditions()
    {
        $local = $this->localKey;
        $this->query->where($this->foreignKey . ' = ?', $this->model->$local);
    }

    public function setEagerParams($params)
    {
        $this->eagerParams = $params;
        return $this;
    }

    protected function eagerLoadingConditions()
    {
        $this->query->in($this->foreignKey, $this->getKeys());
    }

    private function getKeys()
    {
        $local = $this->localKey;
        array_filter($this->eagerParams['results'], function ($getId) use ($local) {
            $this->keys[] = $getId->$local;
        });
        return array_unique($this->keys);
    }

    public function select($fields)
    {
        $value = $this->foreignKey . ','. $fields;
        $this->query->select($value);
        return $this;
    }

    public function getCollection()
    {
        if (isset($this->eagerParams['with'])) {
            $this->eagerLoadingConditions();
            $result = $this->query->get();
        } else {
            $this->query->setModel($this->related);
            $this->lazyLoadingConditions();
            $total = $this->query->get();
            $result = $total[0];
        }
        return $result;
    }

    public function getByEagerloading($collection, $entity)
    {
        $result = [];
        foreach ($collection as $collect) {
            $foreign = $this->foreignKey;
            $local = $this->localKey;
            if ($collect->$foreign == $entity->$local) {
                $result[] = $this->hydrate($collect, $this->related);
            }
        }
        return $result[0];
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
