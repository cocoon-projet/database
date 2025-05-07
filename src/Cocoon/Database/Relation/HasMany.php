<?php
declare(strict_types=1);

namespace Cocoon\Database\Relation;

use Cocoon\Database\Orm;
use Cocoon\Pager\Paginator;
use Cocoon\Utilities\Strings;
use Cocoon\Collection\Collection;
use Cocoon\Pager\PaginatorConfig;
use Cocoon\Database\Query\Builder;

class HasMany
{
    private $query;
    private $model;
    private $related;
    private $foreignKey;
    private $localKey;
    private $eagerParams = [];
    private $keys = [];
    private $paginationRelated = false;
    private $paginationRelatedOptions = [];
    // TODO faire une fonction where
    public function __construct($model, $related, $foreignKey, $localKey)
    {
        $this->query = Builder::init()->from($related::getTableName());
        $this->model = $model;
        $this->related = $related;
        $this->foreignKey = $foreignKey ?? Strings::singular($model::getTableName()) . '_id';
        ;
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
        $value = $this->foreignKey . ',' . $fields;
        $this->query->select($value);
        return $this;
    }

    public function paginate($perpage = 1, $mode = null)
    {
        $this->paginationRelated = true;
        if ($mode != null) {
            $this->paginationRelatedOptions['styling'] = $mode;
        } else {
            $this->paginationRelatedOptions['styling'] = 'all';
        }
        
        $this->paginationRelatedOptions['perpage'] = $perpage;
        return $this;
    }

    public function getCollection()
    {
        if (isset($this->eagerParams['with'])) {
            $this->eagerLoadingConditions();
            $result = $this->query->get();
        } else {
            if ($this->paginationRelated) {
                throw new \Exception('La fonctionnalité de pagination n\'est pas disponible en lazy loading');
            }
            $this->query->setModel($this->related);
            $this->lazyLoadingConditions();
            $result = new Collection($this->query->get());
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
        if ($this->paginationRelated) {
            $array = $result;
            $items = ($count = count($array))
                ? $result
                : new Collection([]);
            $config = new PaginatorConfig($items, $count);
            $config->setPerPage($this->paginationRelatedOptions['perpage']);
            $config->setStyling($this->paginationRelatedOptions['styling']);
            $config->setCssFramework(Orm::getConfig('pagination.renderer'));
            return new Paginator($config);
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
