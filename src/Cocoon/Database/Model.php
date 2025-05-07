<?php
declare(strict_types=1);

namespace Cocoon\Database;

use Exception;
use ArrayAccess;
use Cocoon\Utilities\Strings;
use Cocoon\Database\Query\Builder;
use Cocoon\Database\Exception\ModelException;
use Cocoon\Database\Traits\HasRelationTrait;
use Cocoon\Database\Traits\HasObserversTrait;
use Cocoon\Database\Traits\MutatorAccessorTrait;

/**
 * Class Model
 * @package Cocoon\Database
 */
abstract class Model implements ArrayAccess
{

    /**
     * @var string
     */
    protected static $table = null;
    /**
     * @var int|null
     */
    protected $id = null;
    /**
     * Enregistre les champs date pour renvoyer une instance de Carbon Datetime
     *
     * @var array
     */
    protected $dates = [];
    /**
     *  defaut primary key column
     *
     * @var string
     */
    protected $primaryKey = 'id';
    /**
     * @var bool|null
     */
    protected $isNew = null;

    use MutatorAccessorTrait, HasRelationTrait, HasObserversTrait;

    /**
     * definit l'id pour UPDATE et DELETE
     *
     * Model constructor.
     * @param null|numeric|array $id
     */
    public function __construct($id = null)
    {
        if ($id != null && is_numeric($id)) {
            $this->isNew = false;
            $this->id = $id;
        } elseif (is_array($id)) {
            $this->isNew = false;
            $this->id = $id;
        } else {
            $this->isNew = true;
        }
            $this->relations();
            $this->observe();
    }

    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    public function __set($name, $value)
    {
        $this->setData($name, $value);
    }

    public function __get($name)
    {
        return $this->getData($name);
    }

    public function offsetExists($offset)
    {
        return !is_null($this->getData($offset));
    }

    public function offsetGet($offset)
    {
        return $this->getData($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->setData($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Retourne la clef primaire du model.
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Retourne l'id de l'enregistrement demandée
     *
     * @return array|numeric|int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Retourne le nom de la table de la base de donnée
     *
     * @return string
     */
    public static function getTableName()
    {
        if (static::$table == null) {
            $tableName = preg_replace('/([a-zA-Z]+[\\\\])/i', '', get_called_class());
            static::$table = Strings::tableize($tableName);
        }
        return static::$table;
    }

    /**
     * Enregistre des données dans la table
     * @throws ModelException
     */
    public function save()
    {
        //$this->__unset($this->primaryKey);
        if ($this->isNew) {
            $this->runObserver('beforeSave');
            $this->id = Builder::init()->into(static::getTableName())->insert($this->data);
            $this->runObserver('afterSave');
        } else {
            $this->runObserver('beforeUpdate');
            Builder::init()->from(static::getTableName())
                    ->where($this->primaryKey, $this->getId())
                    ->update($this->data);
            $this->runObserver('afterUpdate');
        }
    }

    /**
     * Incremente un champ
     *
     * @param string $field
     * @param int $more
     * @param array $columns
     */
    public static function increment($field, $more = 1, $columns = null)
    {
        builder::init()->from(static::getTableName())->increment($field, $more, $columns);
    }

    /**
     * Decrement un champs
     *
     * @param string $field
     * @param int $less
     * @param array $columns
     */
    public static function decrement($field, $less = 1, $columns = null)
    {
        builder::init()->from(static::getTableName())->increment($field, $less, $columns);
    }

    /**
     * Enregistre des données dans la table
     *
     * @param array $data
     * @return static
     * @throws ModelException
     */
    public static function create(array $data)
    {
        $model = new static();
        foreach ($data as $key => $value) {
            $model->__set($key, $value);
        }
        $model->save();
        return $model;
    }

    /**
     * Supprime des données dans la table
     * @throws ModelException
     */
    public function delete()
    {
        if (!$this->isNew) {
            $this->runObserver('beforeDelete');
            Builder::init()->from(static::getTableName())->where($this->primaryKey, $this->getId())->delete();
            $this->runObserver('afterDelete');
        }
    }

    /**
     * Supprime tous les enregistrements d'un model
     */
    public function destroy()
    {
        Builder::init()->from(static::getTableName())->delete();
    }

    /**
     * Retourne une liste de données avec ou sans pagination
     *
     * @param string $fields
     * @param null|int|array $paginate ['perpage' => 10, 'styling' => 'basic; 'delta' => 3];
     * @param string $orderByField
     * @param string $order
     * @return array|object
     */
    public static function findAll($fields = '', $paginate = null, $orderByField = 'id', $order = 'desc')
    {
        if (is_array($paginate) or is_numeric($paginate)) {
            $perpage = $paginate['perpage'] ?? $paginate;
            $options['styling'] = $paginate['styling'] ?? null;
            $options['delta'] = $paginate['delta'] ?? null;
            $result = Builder::init(get_called_class())
                    ->select($fields)
                    ->from(static::getTableName())
                    ->orderBy($orderByField, $order)
                    ->paginate($perpage, $options['styling']);
            return $result;
        }
        $data = Builder::init(get_called_class())->select($fields)
                ->from(static::getTableName())
                ->orderBy($orderByField, $order)
                ->get();
        return $data;
    }

    /**
     * Retourne une donnée ou une liste de donnée en precisant
     * l'id ou array(1,2,3)
     *
     * @param null|int|array $id
     * @param string $fields
     * @return array
     */
    public static function find($id = null, $fields = '')
    {
        if (is_array($id)) {
            $data = Builder::init(get_called_class())
                    ->select($fields)
                    ->from(static::getTableName())
                    ->in('id', $id)->get();
        } else {
            $result = Builder::init(get_called_class())
                    ->select($fields)
                    ->from(static::getTableName())
                    ->where('id', $id)->get();
            $data = count($result) == 1 ? $result[0] : null;
        }
        return $data;
    }

    /**
     * Retourne le ou les x derniers enregistrement de la table.
     *
     * @param int $limit
     * @param string $fields
     * @return mixed
     */
    public static function findLast($limit = 1, $fields = '')
    {
        $result = Builder::init(get_called_class())
                ->select($fields)
                ->from(static::getTableName())
                ->last($limit);
        return $result;
    }

    /**
     * Retourne le nombre d'enregistrement de la table.
     *
     * @return int
     */
    public static function count()
    {
        return Builder::init()->from(static::getTableName())->count();
    }

    /**
     * Utilise le query builder pour la class model definit.
     *
     * <code>
     * Users::select('nom')->orderBy('id DESC')->get();
     * </code>
     *
     * @param string $fields
     * @return Query\Builder
     */
    public static function select($fields = '')
    {
        return Builder::init(get_called_class())->select($fields)->from(static::getTableName());
    }
    /**
     * A utilser pour charger les relations par eager loading
     *
     * @param string|array $relations
     * @return Builder
     */
    public static function with($relations)
    {
        return Builder::init(get_called_class())->with($relations)->from(static::getTableName());
    }

    /**
     * Méthode magique pour trouver rapidement des données
     * avec des conditions spécifiques.
     *
     * <code>
     * Users::findBydId(10);
     * Users::findById([10,11,12]);
     * Users::findByNomAndPrenom('Dupont','Henri');
     * Users::findByNom(['Dupont','Gillet']);
     * Users::countByNom(['Dupont','Gillet']);
     * Users::countByNom('Dupont');
     * </code>
     *
     * @param string $method
     * @param string|int|array $args
     * @return int|array
     * @throws \Exception
     */
    public static function __callStatic(string $method, $args)
    {
        if (preg_match('/^(find|count)By(\w+)$/', $method, $matches)) {
            $criteriaKeys = explode('And', $matches[2]);
            $criteriaKeys = array_map('strtolower', $criteriaKeys);
            $keys = [];
            foreach ($criteriaKeys as $val) {
                $keys[] = $val;
            }
            $criteriaValues = array_slice($args, 0, count($keys));

            $conditions = array_combine($keys, $criteriaValues);
            $key = array_keys($conditions);
            $value = array_values($conditions);

            $method = $matches[1];
            if ($method == 'find') {
                $req = static::select();
                if (!is_array($value[0])) {
                    $req->where($key[0], $value[0]);
                } else {
                    $req->in($criteriaKeys[0], $value[0]);
                }
                $conditions = array_slice($conditions, 1);

                if (count($conditions) >= 1) {
                    foreach ($conditions as $key => $value) {
                        $req->and($key, $value);
                    }
                }
                $result = $req->get();
                return count($result) == 1 ? $result[0] : $result;
            } elseif ($method == 'count') {
                $req = static::select('count(*) as nombres');
                if (!is_array($value[0])) {
                    $req->where($key[0], $value[0]);
                } else {
                    $req->in($criteriaKeys[0], $value[0]);
                }
                $conditions = array_slice($conditions, 1);
                if (count($conditions) >= 1) {
                    foreach ($conditions as $key => $value) {
                        $req->and($key, $value);
                    }
                }
                $result = $req->get();
                $total_row = $result[0]->nombres;
                return $total_row;
            } else {
                throw new ModelException('la méthode ' . $method . ' n\'existe pas.');
            }
        } else {
            return Builder::init(get_called_class())->from(static::getTableName())->$method(...$args);
        }
    }
    // TODO tester cette function model::transaction
    /**
     * @param $callback
     * @throws Exception
     */
    public static function transaction($callback)
    {
        $transaction = Builder::init(get_called_class());

        try {
            $transaction->beginTransaction();
            call_user_func($callback);
            $transaction->commit();
        } catch (Exception $ex) {
            $transaction->rollBack();
            throw $ex;
        }
    }

    /**
     * Model observers méthode
     * <code>
     * public function observe()
     * {
     *    static::afterSave(function($model){
     *                Posts::increment('vue', 1, ['categorie_id' => $model->id])
     *           }
     *    );
     * }
     * </code>
     *
     *
     */
    public function observe()
    {
    }

    /**
     * Scopes implémenation
     *
     * @return array
     */
    public static function scopes()
    {
        return [];
    }

    public function relations()
    {
         return [];
    }
}
