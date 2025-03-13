<?php
declare(strict_types=1);

namespace Cocoon\Database\Query;

use Cocoon\Dependency\DI;
use Cocoon\FileSystem\File;
use Cocoon\Pager\Paginator;
use Cocoon\Collection\Collection;
use Cocoon\Pager\PaginatorConfig;

/**
 * Construit les requêtes sql
 *
 * Class Builder
 * @package Cocoon\Database\Query
 */
class Builder
{

    protected $db;
    protected static $instance = null;
    protected $sql = '';
    public static $SQLS = [];
    private $tableName;
    protected $model = null;
    protected $defaultFields = '*';
    protected $setFields;
    protected $addTable;
    protected $with = [];
    protected $alias = '';
    protected $where = [];
    protected $bindParams = [];
    protected $bindParamsWhere = [];
    protected $set = [];
    protected $preparDataInsert = [];
    protected $dataInsert;
    protected $limit = '';
    protected $offset = '';
    protected $groupBy = null;
    protected $having = null;
    protected $between = '';
    protected $order = null;
    protected $distinct = '';
    protected $join = [];
    protected $on = [];
    protected $perpage = 10;
    protected $pagerLinksMode = 'all';
    protected $type = self::SELECT;
    protected $ids = [];
    protected $cache = false;
    protected $cacheParams = [];
    protected $cachePrefix = '_database_cache';
    //protected $entity = null;
    const INSERTING = 0;
    const DELETING = 1;
    const UPDATING = 2;
    const SELECT = 3;


    public function __construct()
    {
        $this->db = DI::get('db.connection');
    }
    public function cache($id, $ttl = 3600): static
    {
        $this->cache = true;
        $this->cacheParams = ['id' => $id, 'ttl' => $ttl];
        return $this;
    }

    public function from($table): static
    {
        $this->tableName = $table;
        return $this;
    }

    public function setModel($model): static
    {
        $this->model = $model;
        return $this;
    }

    public function into($table): static
    {
        $this->tableName = $table;
        return $this;
    }

    public function addTable($table): static
    {
        $this->addTable = ', ' . $table;
        return $this;
    }

    public function insert($data)
    {
        $this->type = self::INSERTING;
        $this->dataInsert = array_keys($data);
        foreach ($data as $k => $v) {
            $this->bindParams[] = $v;
            $this->preparDataInsert[] = '?';
        }
        $stmt = $this->db->prepare($this->getSql());
        if (count($this->getBindParams()) > 0) {
            $stmt->execute($this->getBindParams());
        } else {
            $stmt->execute();
        }
        return $this->lastInsertId();
    }

    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }

    protected function getSet(): string
    {
        $_set = $this->set;
        $set = implode(', ', $_set);
        return $set;
    }
    public function update($data): void
    {
        $this->type = self::UPDATING;
        foreach ($data as $k => $v) {
            if (is_string($v) && (strpos($v, " + ") !== false || strpos($v, " - ") !== false)) {
                $this->set[] = $k . ' = ' . $v;
            } else {
                $this->set[] = $k . ' = ?';
                $this->bindParams[] = $v;
            }
        }
        $stmt = $this->db->prepare($this->getSql());
        if (count($this->getBindParams()) > 0) {
            $stmt->execute($this->getBindParams());
        } else {
            $stmt->execute();
        }
    }

    /**
     * Incrémnente le champs d'une table
     *
     * @param string $field champs de la table
     * @param int $more chiffre a incrémenté
     * @param array $columns pour un champs spécifique
     */
    public function increment($field, $more = 1, $columns = null): void
    {
        $data[$field] = $field . ' + ' . $more;
        if ($columns != null) {
            $keys = array_keys($columns);
            $this->where($keys[0], $columns[$keys[0]])->update($data);
        } else {
            $this->update($data);
        }
    }

    /**
     * Décrémente le champs d'une table
     *
     * @param string $field champs de la table
     * @param int $less chiffre a décrémenté
     * @param array $columns pour un champs spécifique
     */
    public function decrement($field, $less = 1, $columns = null): void
    {
        $data[$field] = $field . ' -  ' . $less;
        if ($columns != null) {
            $keys = array_keys($columns);
            $this->where($keys[0], $columns[$keys[0]])->update($data);
            $this->where($columns)->update($data);
        } else {
            $this->update($data);
        }
    }

    public function delete(): void
    {
        $this->type = self::DELETING;
        $stmt = $this->db->prepare($this->getSql());
        if (count($this->getBindParams()) > 0) {
            $stmt->execute($this->getBindParams());
        } else {
            $stmt->execute();
        }
    }
    protected function getAddTable()
    {
        return $this->addTable;
    }

    public function alias($alias): static
    {
        $this->alias = ' AS ' . $alias . ' ';
        return $this;
    }

    protected function getAlias()
    {
        return $this->alias;
    }

    /**
     * Initialise le Query Builder
     *
     * @param null|string $model
     * @return Builder
     */
    public static function init($model = null): ?Builder
    {
        self::$instance = new Builder();
        if ($model != null) {
            self::$instance->setModel($model);
        }
        return self::$instance;
    }

    /**
     * Selection des champs de la table
     *
     * @param string $fields
     * @return $this
     */
    public function select($fields): static
    {
        $this->setFields = $fields;
        return $this;
    }

    /**
     * Retounne le nombre d'enregistrement d'une table
     *
     * @return int
     */
    public function count()
    {
        $this->select('count(*) as total ');
        $result = $this->get();
        $total = $result[0]->total;
        return $total;
    }

    /**
     * Retourne le nom de la table
     *
     * @return string
     */
    protected function getTableName(): string
    {
        return strtolower($this->tableName);
    }

    /**
     * @param array $args
     * @return $this
     */
    public function where(...$args): static
    {
        $count = count($args);
        if ($count == 2) {
            $condition = $args[0] . ' = ?';
            $bindParam = $args[1];
        } elseif ($count == 3) {
            $condition = $args[0] . ' ' . $args[1] . ' ?';
            $bindParam = $args[2];
        } else {
            $condition = $args[0];
            $bindParam = null;
        }
        $this->where[] = $condition;
        $this->resolveBindParams($bindParam);
        return $this;
    }

    /**
     * Résolutions des paramètres (bind params)
     *
     * @param $bindParams
     */
    protected function resolveBindParams($bindParams): void
    {
        if ($bindParams != null) {
            if (is_array($bindParams)) {
                foreach ($bindParams as $param) {
                    $this->bindParamsWhere[] = $param;
                }
            } else {
                $this->bindParamsWhere[] = $bindParams;
            }
        }
    }

    public function not(...$args): static
    {
        $count = count($args);
        if ($count == 2) {
            $condition = $args[0] . ' = ?';
            $bindParam = $args[1];
        } elseif ($count == 3) {
            $condition = $args[0] . ' ' . $args[1] . ' ?';
            $bindParam = $args[2];
        } else {
            $condition = $args[0];
            $bindParam = null;
        }
        $this->where[] = ' NOT ' . $condition;
        $this->resolveBindParams($bindParam);
        return $this;
    }

    public function and(...$args): static
    {
        $count = count($args);
        if ($count == 2) {
            $condition = $args[0] . ' = ?';
            $bindParam = $args[1];
        } elseif ($count == 3) {
            $condition = $args[0] . ' ' . $args[1] . ' ?';
            $bindParam = $args[2];
        } else {
            $condition = $args[0];
            $bindParam = null;
        }
        $this->where[] = 'AND ' . $condition;
        $this->resolveBindParams($bindParam);
        return $this;
    }

    public function andIn($field = '', array $bindParam = null): static
    {
        $bind = count($bindParam);
        $i = 1;
        $ret = [];
        while ($i <= $bind) {
            $ret[] = '?';
            $i++;
        }
        $this->where[] = ' AND ' . $field . ' IN (' . implode(', ', $ret) . ')';
        $this->resolveBindParams($bindParam);

        return $this;
    }

    public function andNotIn($field = '', array $bindParam = null): static
    {
        $bind = count($bindParam);
        $i = 1;
        $ret = [];
        while ($i <= $bind) {
            $ret[] = '?';
            $i++;
        }
        $this->where[] = ' AND NOT ' . $field . ' IN (' . implode(', ', $ret) . ')';
        $this->resolveBindParams($bindParam);

        return $this;
    }

    public function or(...$args): static
    {
        $count = count($args);
        if ($count == 2) {
            $condition = $args[0] . ' = ?';
            $bindParam = $args[1];
        } elseif ($count == 3) {
            $condition = $args[0] . ' ' . $args[1] . ' ?';
            $bindParam = $args[2];
        } else {
            $condition = $args[0];
            $bindParam = null;
        }
        $this->where[] = 'OR ' . $condition;
        $this->resolveBindParams($bindParam);
        return $this;
    }

    public function andNot(...$args): static
    {
        $count = count($args);
        if ($count == 2) {
            $condition = $args[0] . ' = ?';
            $bindParam = $args[1];
        } elseif ($count == 3) {
            $condition = $args[0] . ' ' . $args[1] . ' ?';
            $bindParam = $args[2];
        } else {
            $condition = $args[0];
            $bindParam = null;
        }
        $this->where[] = 'AND NOT ' . $condition;
        $this->resolveBindParams($bindParam);
        return $this;
    }

    public function in($field = '', array $bindParam = null): static
    {
        $bind = count($bindParam);
        $i = 1;
        $ret = [];
        while ($i <= $bind) {
            $ret[] = '?';
            $i++;
        }
        $this->where[] = $field . ' IN (' . implode(', ', $ret) . ')';
        $this->resolveBindParams($bindParam);

        return $this;
    }

    public function notIn($field, array $bindParam = null): static
    {
        $bind = count($bindParam);
        $i = 1;
        $ret = [];
        while ($i <= $bind) {
            $ret[] = '?';
            $i++;
        }
        $this->where[] = $field . ' NOT IN (' . implode(', ', $ret) . ')';
        $this->resolveBindParams($bindParam);

        return $this;
    }

    protected function getWhere(): string
    {
        $_where = $this->where;
        $where = implode(' ', $_where);
        return $where;
    }

    protected function getBindParams(): array
    {
        return array_merge($this->bindParams, $this->bindParamsWhere);
    }

    protected function getSelect()
    {
        if (empty($this->setFields)) {
            return $this->defaultFields;
        } else {
            return $this->setFields;
        }
    }

    public function limit($limitCount, $iOffset = 0): static
    {
        $this->limit = $limitCount;
        $this->offset = $iOffset;
        return $this;
    }

    protected function getLimit()
    {
        return $this->db->limit($this->limit, $this->offset);
    }

    public function groupBy(): static
    {
        $args = func_get_args();
        $this->groupBy = implode(",", $args);
        return $this;
    }

    protected function getGroupBy()
    {
        return $this->groupBy;
    }
    // TODO voir orHaving
    public function having($cond, $bindParam = null)
    {
        $this->having = $cond;
        if ($bindParam != null) {
            $this->bindParamsWhere[] = $bindParam;
        }
        return $this;
    }
    // TODO a voir !
    public function orHaving($cond, $bindParam = null): static
    {
        $this->having = $cond;
        if ($bindParam != null) {
            $this->bindParamsWhere[] = $bindParam;
        }
        return $this;
    }

    protected function getHaving()
    {
        return $this->having;
    }
    /**
     * <code>
     * Animal::select()->between('id', 10, 24);
     * Animal::select()->between('id', 10, 24)->and('espece', chien');
     * </code>
     */
    public function between($condition, $between1, $between2): static
    {
        //$this->where[] = $condition;
        $this->between = ' BETWEEN ' . $between1 . ' AND ' . $between2;
        $this->where[] = '(' . $condition . $this->between . ')';
        return $this;
    }
    /**
     * <code>
     * Animal::select()->notBetween('id', 10, 24);
     * Animal::select()->notBetween('id', 10, 24)->and("espece = 'chien'");
     * </code>
     */

    public function notBetween($condition, $between1, $between2): static
    {
        //$this->where[] = $condition;
        $this->between = ' NOT BETWEEN ' . $between1 . ' AND ' . $between2;
        $this->where[] = '(' . $condition . $this->between . ')';
        return $this;
    }

    protected function getBetween()
    {
        return $this->between;
    }

    public function orderBy($field, $orderBy = 'desc'): static
    {
        $this->order = ' ORDER BY ' . $field . ' ' . strtolower($orderBy);

        return $this;
    }

    protected function getOrder()
    {
        return $this->order;
    }

    public function leftJoin($table, $on = null): static
    {
        $table = strtolower($table);
        $this->join[] = ' LEFT JOIN ' . $table . ' ON ' . $on;
        return $this;
    }

    public function innerJoin($table, $on = null): static
    {
        $table = strtolower($table);
        $this->join[] = ' INNER JOIN ' . $table . ' ON ' . $on;
        return $this;
    }

    public function getJoin(): string
    {
        $join = implode(' ', $this->join);
        return $join;
    }

    public function paginate($perpage = null, $mode = null): Paginator
    {
        if ($perpage != null) {
            $this->perpage = $perpage;
        }
        if ($mode != null) {
            $this->pagerLinksMode = $mode;
        }
        $items = ($count = count($this->get()))
        ? $this
        : new Collection([]);
        $config = new PaginatorConfig($items, $count);
        $config->setPerPage($this->perpage);
        $config->setstyling($this->pagerLinksMode);
        return new Paginator($config);
    }


    public function getSql(): string
    {
        switch ($this->type) {
            case 0:
                $this->sql = "INSERT INTO "
                    . $this->getTableName()
                    . '(' . implode(',', $this->dataInsert) . ')'
                    . ' VALUES (' . implode(', ', $this->preparDataInsert) . ')';
                break;
            case 1:
                $where = $this->getWhere();
                $this->sql = 'DELETE FROM ' . $this->getTableName() .
                    (!empty($where) ? (' WHERE ' . $where . '') : '');
                break;
            case 2:
                $where = $this->getWhere();
                $_set = $this->getSet();
                $this->sql = 'UPDATE ' . $this->getTableName() .
                    (!empty($_set) ? (' SET ' . $_set . '') : '') .
                    (!empty($where) ? (' WHERE ' . $where . '') : '');
                break;
            case 3:
                //TODO between a tester
                $iLimit = $this->getLimit();
                $where = $this->getWhere();
                $groupBy = $this->getGroupBy();
                $having = $this->getHaving();
                $order = $this->getOrder();

                $this->sql = 'SELECT ' . $this->getSelect() .
                    ' FROM ' . $this->getTableName() . $this->getAlias() .
                    $this->getAddTable() . $this->getJoin() .
                    (!empty($where) ? (' WHERE ' . $where . '') : '') .
                    (!empty($groupBy) ? (' GROUP BY ' . $groupBy . '') : '') .
                    (!empty($having) ? (' HAVING ' . $having . '') : '') .
                    ($order != null ? ($order) : '') .
                    $iLimit;
        }
        static::$SQLS[] = trim($this->sql);
        return trim($this->sql);
    }

    /**
     * Retourne le ou les premiers enregistrements
     *
     * @param int $first
     * @return array|int
     */
    public function first(int $first = 1)
    {
        $result = $this->orderBy('id', 'asc')->limit($first)->get();
        return count($result) == 1 ? $result[0] : $result;
    }

    /**
     * Retourne le ou les dernierss enregistrements
     *
     * @param int $last
     * @return array
     */
    public function last($last = 1)
    {
        $result = $this->orderBy('id')->limit($last)->get();
        return count($result) == 1 ? $result[0] : $result;
    }

    /**
     * Recupère le nom d'une colonne, indéxé par la colonne id;
     *
     * @param string $field
     * @param string $id
     * @return array
     */
    public function lists($field, $id = 'id')
    {
        $result = $this->get();
        return (new Collection($result))->lists($field, $id)->toArray();
    }

    /**
     * Execute la requete sql et la retourne
     *
     * @return array
     */
    public function get()
    {
        if ($this->cache == true && File::hasAndIsExpired(DI::get('db.cache.path')
                . md5($this->cacheParams['id'])
                . $this->cachePrefix, $this->cacheParams['ttl'])
        ) {
            return unserialize(File::read(DI::get('db.cache.path')
                . md5($this->cacheParams['id'])
                . $this->cachePrefix));
        }

        $stmt = $this->db->prepare($this->getSql());
        if (count($this->getBindParams()) > 0) {
            $stmt->execute($this->getBindParams());
        } else {
            $stmt->execute();
        }
        $result = $stmt->fetchAll();

        if ($this->model != null) {
            $stmt = null;
            $result = $this->hydrateModel($result);
        }


        $stmt = null;
        if ($this->cache) {
            File::put(DI::get('db.cache.path') . md5($this->cacheParams['id'])
                . $this->cachePrefix, serialize($result));
        }
        return $result;
    }

    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    public function commit()
    {
        $this->db->commit();
    }

    public function rollBack()
    {
        $this->db->rollBack();
    }

    /**
     * Hydrate le model spécifié dans Builder::init($model);
     *
     * @param $data
     * @return array
     */
    private function hydrateModel($data): array
    {
        $params = [];
        $collect = [];
        //------------------------ EAGER LOADIND
        if (!empty($this->with)) {
            $params['results'] = $data;
            $params['with'] = $this->with;
            $collect = (new $this->model())->loadRelationsByEagerLoading($params);
        }
        //--------------------------
        $result = [];
        foreach ($data as $dbColumn) {
            $entity = new $this->model();
            foreach ($dbColumn as $column => $property) {
                $entity->$column = $property;
            }
            if (!empty($this->with)) {
                $entity->getRelationByEagerLoading($collect, $params['with'], $entity);
            }
            $result[] = $entity;
        }
        return $result;
    }

    public function with($relations = null): static
    {
        if (is_string($relations)) {
            $this->with[] = $relations;
        }
        $this->with = array_unique($relations);
        return $this;
    }

    // TODO implements scope a tester
    public function __call($name, $arguments)
    {
        $model = $this->model;
        if (isset($model::scopes()[$name])) {
            return $model::scopes()[$name]($this);
        }
    }

    public function __destruct()
    {
        $this->db = null;
    }
}
