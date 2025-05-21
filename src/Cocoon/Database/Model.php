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
    protected static ?string $table = null;
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
            $this->hasDates();
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
        return (int) $this->id;
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
     * @param int|null $id
     * @return static
     * @throws ModelException
     */
    public static function create(array $data, $id = null)
    {
        $model = new static($id);
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
     * @param string $fields Champs à sélectionner
     * @param int|array|null $paginate Options de pagination :
     *      - int : nombre d'éléments par page
     *      - array : ['perpage' => 10, 'styling' => 'basic', 'delta' => 3]
     * @param string $orderByField Champ de tri
     * @param string $order Direction du tri ('asc' ou 'desc')
     * @return array|object Résultat de la requête
     */
    public static function findAll(
        string $fields = '',
        int|array|null $paginate = null,
        string $orderByField = 'id',
        string $order = 'desc'
    ): array|object {
        $query = Builder::init(get_called_class())
            ->select($fields)
            ->from(static::getTableName())
            ->orderBy($orderByField, $order);

        if ($paginate === null) {
            return $query->get();
        }

        $perpage = is_array($paginate) ? ($paginate['perpage'] ?? 10) : $paginate;
        $options = [
            'styling' => $paginate['styling'] ?? null,
            'delta' => $paginate['delta'] ?? null
        ];

        return $query->paginate($perpage, $options['styling']);
    }

    /**
     * Retourne une donnée ou une liste de données en fonction de l'ID fourni
     *
     * @param int|array|null $id ID unique ou tableau d'IDs à rechercher
     * @param string $fields Champs à sélectionner
     * @return array|object|null Résultat de la recherche
     *      - Un seul objet si un ID unique est fourni
     *      - Un tableau d'objets si un tableau d'IDs est fourni
     *      - null si aucun résultat n'est trouvé
     */
    public static function find(int|array|null $id = null, string $fields = ''): array|object|null
    {
        $query = Builder::init(get_called_class())
            ->select($fields)
            ->from(static::getTableName());

        if ($id === null) {
            return null;
        }

        if (is_array($id)) {
            return $query->in('id', $id)->get();
        }

        $result = $query->where('id', $id)->get();
        return count($result) === 1 ? $result[0] : null;
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
     * Gère les appels de méthodes statiques dynamiques sur le modèle.
     * Permet d'utiliser des méthodes comme :
     * - findBy* : User::findByEmail('test@example.com')
     * - countBy* : User::countByStatus('active')
     * - updateBy* : User::updateByEmail('test@example.com', ['status' => 'inactive'])
     *
     * @param string $method Nom de la méthode appelée
     * @param array $args Arguments passés à la méthode
     * @return mixed Résultat de la méthode
     * @throws ModelException Si la méthode n'existe pas
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        // Gestion des méthodes findBy* et countBy*
        if (str_starts_with($method, 'findBy') || str_starts_with($method, 'countBy')) {
            return self::handleDynamicFindOrCount($method, $args);
        }

        // Gestion des méthodes updateBy*
        if (str_starts_with($method, 'updateBy')) {
            return self::handleDynamicUpdate($method, $args);
        }

        // Transmission des autres appels au Query Builder
        return Builder::init(static::class)
            ->from(static::getTableName())
            ->$method(...$args);
    }

    /**
     * Gère les appels dynamiques des méthodes findBy* et countBy*.
     * Exemples :
     * - findByEmailAndStatus('test@example.com', 'active')
     * - countByStatusAndType('active', 'user')
     *
     * @param string $method Nom de la méthode appelée
     * @param array $args Arguments passés à la méthode
     * @return mixed Résultat de la recherche ou le nombre d'éléments
     * @throws ModelException Si une erreur survient
     */
    protected static function handleDynamicFindOrCount(string $method, array $args): mixed
    {
        $isFind = str_starts_with($method, 'findBy');
        $prefix = $isFind ? 'findBy' : 'countBy';
        $criteriaString = substr($method, strlen($prefix));
        
        // Découpage des critères par 'And' et conversion en minuscules
        $criteriaKeys = array_map(
            'strtolower',
            explode('And', $criteriaString)
        );

        // Création du tableau des conditions
        $conditions = array_combine(
            $criteriaKeys,
            array_slice($args, 0, count($criteriaKeys))
        );

        // Construction de la requête
        $query = $isFind ? static::select() : static::select('count(*) as count');
        
        // Ajout de la première condition
        $firstKey = array_key_first($conditions);
        $firstValue = $conditions[$firstKey];
        
        if (is_array($firstValue)) {
            $query->in($firstKey, $firstValue);
        } else {
            $query->where($firstKey, $firstValue);
        }

        // Ajout des conditions restantes
        $remainingConditions = array_slice($conditions, 1, null, true);
        foreach ($remainingConditions as $key => $value) {
            $query->and($key, $value);
        }

        // Exécution de la requête et retour des résultats
        $result = $query->get();

        if ($isFind) {
            return count($result) === 1 ? $result[0] : $result;
        }

        return $result[0]->count;
    }

    /**
     * Gère les appels dynamiques des méthodes updateBy*.
     * Exemple : updateByEmail('test@example.com', ['status' => 'inactive'])
     *
     * @param string $method Nom de la méthode appelée
     * @param array $args Arguments passés à la méthode
     * @return bool True si la mise à jour a été effectuée
     */
    protected static function handleDynamicUpdate(string $method, array $args): bool
    {
        $field = lcfirst(substr($method, 8));
        static::select()
            ->where($field, $args[0])
            ->update($args[1]);
        return true;
    }

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

    public function hasDates()
    {
        if(!empty($this->dates)) {
            foreach($this->dates as $date) {
                if($this->isNew) {
                    if(str_contains($date, 'created')) {
                        $this->setData($date, date('Y-m-d H:i:s'));
                    } else if(str_contains($date, 'updated')) {
                        $this->setData($date, date('Y-m-d H:i:s'));
                    }
                } else {
                    if(str_contains($date, 'updated')) {
                        $this->setData($date, date('Y-m-d H:i:s'));
                    }
                }
            }
        }
        return false;
    }
}
