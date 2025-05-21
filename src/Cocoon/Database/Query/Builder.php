<?php
declare(strict_types=1);

namespace Cocoon\Database\Query;

use Cocoon\Database\Orm;
use Cocoon\FileSystem\File;
use Cocoon\Pager\Paginator;
use Cocoon\Database\Query\Cast;
use Cocoon\Collection\Collection;
use Cocoon\Pager\PaginatorConfig;
use Cocoon\Database\Exception\QueryException;
use Cocoon\Database\Exception\ModelException;
use Cocoon\Database\Exception\CacheException;

/**
 * Builder de requêtes SQL
 *
 * Cette classe permet de construire et d'exécuter des requêtes SQL de manière fluide et sécurisée.
 * Elle supporte les opérations CRUD (Create, Read, Update, Delete) ainsi que des fonctionnalités
 * avancées comme la pagination, le cache et l'eager loading des relations.
 *
 * @package Cocoon\Database\Query
 */
class Builder
{
    /**
     * Instance de la connexion à la base de données
     *
     * @var \PDO
     */
    protected $db;

    /**
     * Instance unique du Builder (pattern Singleton)
     *
     * @var self|null
     */
    protected static $instance = null;

    /**
     * Requête SQL générée
     *
     * @var string
     */
    protected $sql = '';

    /**
     * Historique des requêtes SQL exécutées
     *
     * @var array
     */
    public static $SQLS = [];

    /**
     * Nom de la table principale
     *
     * @var string
     */
    private $tableName;

    /**
     * Classe du modèle associé
     *
     * @var string|null
     */
    protected $model = null;

    /**
     * Champs par défaut pour les requêtes SELECT
     *
     * @var string
     */
    protected $defaultFields = '*';

    /**
     * Champs spécifiés pour la requête
     *
     * @var string|null
     */
    protected $setFields;

    /**
     * Tables additionnelles pour les jointures
     *
     * @var string|null
     */
    protected $addTable;

    /**
     * Relations à charger avec eager loading
     *
     * @var array
     */
    protected $with = [];

    /**
     * Alias de la table principale
     *
     * @var string
     */
    protected $alias = '';

    /**
     * Conditions WHERE
     *
     * @var array
     */
    protected $where = [];

    /**
     * Paramètres de liaison pour la requête
     *
     * @var array
     */
    protected $bindParams = [];

    /**
     * Paramètres de liaison pour les conditions WHERE
     *
     * @var array
     */
    protected $bindParamsWhere = [];

    /**
     * Champs à mettre à jour (UPDATE)
     *
     * @var array
     */
    protected $set = [];

    /**
     * Données à insérer (INSERT)
     *
     * @var array
     */
    protected $preparDataInsert = [];

    /**
     * Colonnes pour l'insertion
     *
     * @var array
     */
    protected $dataInsert;

    /**
     * Limite de résultats
     *
     * @var string
     */
    protected $limit = '';

    /**
     * Offset pour la pagination
     *
     * @var string
     */
    protected $offset = '';

    /**
     * Clause GROUP BY
     *
     * @var string|null
     */
    protected $groupBy = null;

    /**
     * Clause HAVING
     *
     * @var string|null
     */
    protected $having = null;

    /**
     * Clause BETWEEN
     *
     * @var string
     */
    protected $between = '';

    /**
     * Clause ORDER BY
     *
     * @var string|null
     */
    protected $order = null;

    /**
     * Clause DISTINCT
     *
     * @var string
     */
    protected $distinct = '';

    /**
     * Jointures
     *
     * @var array
     */
    protected $join = [];

    /**
     * Conditions ON pour les jointures
     *
     * @var array
     */
    protected $on = [];

    /**
     * Nombre d'éléments par page pour la pagination
     *
     * @var int
     */
    protected $perpage = 10;

    /**
     * Colonnes sélectionnées
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Mode d'affichage des liens de pagination
     *
     * @var string
     */
    protected $pagerLinksMode = 'all';

    /**
     * Type de requête en cours
     *
     * @var int
     */
    protected $type = self::SELECT;

    /**
     * IDs pour les requêtes IN
     *
     * @var array
     */
    protected $ids = [];

    /**
     * État du cache
     *
     * @var bool
     */
    protected $cache = false;

    /**
     * Paramètres du cache
     *
     * @var array
     */
    protected $cacheParams = [];

    /**
     * Préfixe pour les fichiers de cache
     *
     * @var string
     */
    protected $cachePrefix = '_database_cache';

    /**
     * Constantes pour les types de requêtes
     */
    const INSERTING = 0;
    const DELETING = 1;
    const UPDATING = 2;
    const SELECT = 3;


    public function __construct()
    {
        $this->db = Orm::getConfig('db.connection');
        Cast::setDatabaseType();
    }

    /**
     * Active le cache pour la requête en cours
     *
     * @param string $id Identifiant unique pour le cache
     * @param int $ttl Durée de vie du cache en secondes (par défaut: 3600)
     * @return self
     */
    public function cache(string $id, int $ttl = 3600): static
    {
        $this->cache = true;
        //dumpe(Orm::getConfig('db.cache.path'));
        File::initialize(Orm::getConfig('base.path'));
        $this->cacheParams = ['id' => $id, 'ttl' => $ttl];
        return $this;
    }

    /**
     * Spécifie la table principale pour la requête
     *
     * @param string $table Nom de la table
     * @return self
     */
    public function from(string $table): static
    {
        $this->tableName = $table;
        return $this;
    }

    /**
     * Définit le modèle associé à la requête
     *
     * @param string $model Nom de la classe du modèle
     * @return self
     */
    public function setModel(string $model): static
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Spécifie la table pour une insertion
     *
     * @param string $table Nom de la table
     * @return self
     */
    public function into(string $table): static
    {
        $this->tableName = $table;
        return $this;
    }

    /**
     * Ajoute une table pour une jointure
     *
     * @param string $table Nom de la table à ajouter
     * @return self
     */
    public function addTable(string $table): static
    {
        $this->addTable = ', ' . $table;
        return $this;
    }

    /**
     * Insère des données dans la table
     *
     * @param array $data Données à insérer [colonne => valeur]
     * @return int|string ID de la dernière insertion
     */
    public function insert(array $data): int|string
    {
        $this->type = self::INSERTING;
        $this->dataInsert = array_keys($data);
        foreach ($data as $v) {
            $this->bindParams[] = $v ?? null;
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

    /**
     * Retourne l'ID de la dernière insertion
     *
     * @return int|string
     */
    public function lastInsertId(): int|string
    {
        return $this->db->lastInsertId();
    }

    /**
     * Met à jour des données dans la table
     *
     * @param array $data Données à mettre à jour [colonne => valeur]
     * @return void
     */
    public function update(array $data): void
    {
        $this->type = self::UPDATING;
        foreach ($data as $k => $v) {
            $this->set[] = $k . ' = ?';
            $this->bindParams[] = $v;
        }
        $stmt = $this->db->prepare($this->getSql());
        if (count($this->getBindParams()) > 0) {
            $stmt->execute($this->getBindParams());
        } else {
            $stmt->execute();
        }
    }

    /**
     * Supprime des enregistrements de la table
     *
     * @return void
     */
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

    /**
     * Initialise une nouvelle instance du Builder
     *
     * @param string|null $model Nom de la classe du modèle
     * @return self|null
     */
    public static function init(?string $model = null): ?self
    {
        self::$instance = new self();
        if ($model !== null) {
            self::$instance->setModel($model);
        }
        return self::$instance;
    }

    /**
     * Sélectionne des champs spécifiques
     *
     * @param string|array $fields Champs à sélectionner
     * @return self
     */
    public function select(string|array $fields): static
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        foreach ($fields as $column) {
            if ($column instanceof Raw) {
                $this->columns[] = $column->getValue();
            } else {
                $this->columns[] = $column;
            }
        }
        $this->setFields = implode(', ', $this->columns);
        return $this;
    }

    /**
     * Retourne le nombre total d'enregistrements
     *
     * @return int
     */
    public function count(): int
    {
        $this->select('count(*) as total');
        $result = $this->get();
        return (int) $result[0]->total;
    }

    protected function getSet(): string
    {
        $_set = $this->set;
        $set = implode(', ', $_set);
        return $set;
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
        $this->set[] = $field . ' = ' . $field . ' + ?';
        $this->bindParams[] = $more;
        if ($columns != null) {
            $keys = array_keys($columns);
            $this->where($keys[0], $columns[$keys[0]])->update([]);
        } else {
            $this->update([]);
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
        $this->set[] = $field . ' = ' . $field . ' - ?';
        $this->bindParams[] = $less;
        if ($columns != null) {
            $keys = array_keys($columns);
            $this->where($keys[0], $columns[$keys[0]])->update([]);
        } else {
            $this->update([]);
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
     * @param mixed $bindParams
     */
    protected function resolveBindParams($bindParams): void
    {
        if ($bindParams !== null) {
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

    /**
     * Génère la clause LIMIT pour la requête SQL
     *
     * @return string La clause LIMIT formatée
     * @throws QueryException Si les valeurs de limite ou d'offset sont invalides
     */
    protected function getLimit(): string
    {
        $limit = $this->limit ? (int)$this->limit : 0;
        $offset = $this->offset ? (int)$this->offset : 0;

        if ($limit < 0 || $offset < 0) {
            throw new QueryException(
                'Les valeurs de limite et d\'offset doivent être positives',
                QueryException::INVALID_PARAMETER,
                null,
                ['limit' => $limit, 'offset' => $offset]
            );
        }

        return $this->db->limit($limit, $offset);
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

    /**
     * Ajoute une clause HAVING
     *
     * @param string|array $column Colonne ou condition complète
     * @param string|null $operator Opérateur de comparaison
     * @param mixed|null $value Valeur à comparer
     * @return $this
     */
    public function having($column, $operator = null, $value = null): static
    {
        // Si 2 paramètres: $column est le nom de colonne, $operator est la valeur
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        // Si 1 paramètre: $column est la condition complète
        if ($operator === null) {
            $this->having = $column;
        } else {
            // Si 2 ou 3 paramètres: construire la condition
            $this->having = $column . ' ' . $operator . ' ?';
            $this->bindParamsWhere[] = $value;
        }

        return $this;
    }

    /**
     * Ajoute une clause OR HAVING
     *
     * @param string|array $column Colonne ou condition complète
     * @param string|null $operator Opérateur de comparaison
     * @param mixed|null $value Valeur à comparer
     * @return $this
     */
    public function orHaving($column, $operator = null, $value = null): static
    {
        // Si 2 paramètres: $column est le nom de colonne, $operator est la valeur
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        // Si 1 paramètre: $column est la condition complète
        if ($operator === null) {
            // Concaténer avec la condition HAVING existante si elle existe
            $this->having = $this->having ? $this->having . ' OR ' . $column : $column;
        } else {
            // Si 2 ou 3 paramètres: construire la condition
            $condition = $column . ' ' . $operator . ' ?';
            // Concaténer avec la condition HAVING existante si elle existe
            $this->having = $this->having ? $this->having . ' OR ' . $condition : $condition;
            $this->bindParamsWhere[] = $value;
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

    protected function getOrder(): string
    {
        return $this->order ?? '';
    }

    /**
     * Effectue une jointure LEFT JOIN
     *
     * @param string $table Nom de la table à joindre
     * @param string|null $on Condition de jointure
     * @return self
     */
    public function leftJoin(string $table, ?string $on = null): static
    {
        $table = strtolower($table);
        $this->join[] = ' LEFT JOIN ' . $table . ' ON ' . $on;
        return $this;
    }

    /**
     * Effectue une jointure INNER JOIN
     *
     * @param string $table Nom de la table à joindre
     * @param string|null $on Condition de jointure
     * @return self
     */
    public function innerJoin(string $table, ?string $on = null): static
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

    /**
     * Récupère les éléments pour la pagination
     *
     * @return array{items: Collection|self|array, count: int} Tableau contenant les éléments et leur nombre
     */
    private function getItemsForPagination(): array
    {
        $results = $this->get();
        $count = count($results);
        $items = $count > 0 ? $this : new Collection([]);
        
        return [
            'items' => $items,
            'count' => $count
        ];
    }

    /**
     * Crée la configuration du paginateur
     *
     * @param array{items: Collection|self|array, count: int} $data Données de pagination
     * @return PaginatorConfig Configuration du paginateur
     */
    private function createPaginatorConfig(array $data): PaginatorConfig
    {
        $config = new PaginatorConfig($data['items'], $data['count']);
        $config->setPerPage($this->perpage);
        $config->setStyling($this->pagerLinksMode);
        $config->setCssFramework(Orm::getConfig('pagination.renderer'));
        
        return $config;
    }

    /**
     * Pagine les résultats de la requête
     *
     * @param int|null $perpage Nombre d'éléments par page
     * @param string|null $mode Mode d'affichage des liens de pagination ('basic', 'all', etc.)
     * @return Paginator Instance du paginateur
     * @throws \InvalidArgumentException Si le nombre d'éléments par page est invalide
     */
    public function paginate(?int $perpage = null, ?string $mode = null): Paginator
    {
        $this->validatePaginationParameters($perpage, $mode);
        
        $paginationData = $this->getItemsForPagination();
        $config = $this->createPaginatorConfig($paginationData);
        
        return new Paginator($config);
    }

    /**
     * Valide les paramètres de pagination
     *
     * @param int|null $perpage Nombre d'éléments par page
     * @param string|null $mode Mode d'affichage
     * @throws QueryException Si les paramètres sont invalides
     */
    private function validatePaginationParameters(?int $perpage, ?string $mode): void
    {
        if ($perpage !== null) {
            if ($perpage <= 0) {
                throw new QueryException(
                    'Le nombre d\'éléments par page doit être supérieur à 0',
                    QueryException::INVALID_PARAMETER,
                    null,
                    ['perpage' => $perpage]
                );
            }
            $this->perpage = $perpage;
        }

        if ($mode !== null) {
            $this->pagerLinksMode = $mode;
        }
    }

    /**
     * Génère la requête SQL en fonction du type d'opération
     *
     * @return string La requête SQL générée
     */
    public function getSql(): string
    {
        $sql = match ($this->type) {
            0 => $this->buildInsertSql(),
            1 => $this->buildDeleteSql(),
            2 => $this->buildUpdateSql(),
            3 => $this->buildSelectSql(),
            default => throw new QueryException(
                "Type de requête invalide: {$this->type}",
                QueryException::INVALID_PARAMETER,
                null,
                ['type' => $this->type]
            )
        };

        static::$SQLS[] = trim($sql);
        return trim($sql);
    }

    /**
     * Construit la requête SQL pour une insertion
     *
     * @return string
     */
    private function buildInsertSql(): string
    {
        return "INSERT INTO " . $this->getTableName()
            . '(' . implode(',', $this->dataInsert) . ')'
            . ' VALUES (' . implode(', ', $this->preparDataInsert) . ')';
    }

    /**
     * Construit la requête SQL pour une suppression
     *
     * @return string
     */
    private function buildDeleteSql(): string
    {
        $where = $this->getWhere();
        return 'DELETE FROM ' . $this->getTableName()
            . (!empty($where) ? ' WHERE ' . $where : '');
    }

    /**
     * Construit la requête SQL pour une mise à jour
     *
     * @return string
     */
    private function buildUpdateSql(): string
    {
        $where = $this->getWhere();
        $set = $this->getSet();
        
        return 'UPDATE ' . $this->getTableName()
            . (!empty($set) ? ' SET ' . $set : '')
            . (!empty($where) ? ' WHERE ' . $where : '');
    }

    /**
     * Construit la requête SQL pour une sélection
     *
     * @return string
     */
    private function buildSelectSql(): string
    {
        $where = $this->getWhere();
        $groupBy = $this->getGroupBy();
        $having = $this->getHaving();
        $order = $this->getOrder();
        $limit = $this->getLimit();

        return 'SELECT ' . $this->getSelect()
            . ' FROM ' . $this->getTableName()
            . $this->getAlias()
            . $this->getAddTable()
            . $this->getJoin()
            . (!empty($where) ? ' WHERE ' . $where : '')
            . (!empty($groupBy) ? ' GROUP BY ' . $groupBy : '')
            . (!empty($having) ? ' HAVING ' . $having : '')
            . ($order !== null ? $order : '')
            . $limit;
    }

    /**
     * Retourne les premiers enregistrements
     *
     * @param int $first Nombre d'enregistrements à retourner
     * @return array|object Résultat de la requête
     */
    public function first(int $first = 1): array|object
    {
        $result = $this->orderBy('id', 'asc')->limit($first)->get();
        return count($result) === 1 ? $result[0] : $result;
    }

    /**
     * Retourne les derniers enregistrements
     *
     * @param int $last Nombre d'enregistrements à retourner
     * @return array|object Résultat de la requête
     */
    public function last(int $last = 1): array|object
    {
        $result = $this->orderBy('id')->limit($last)->get();
        return count($result) === 1 ? $result[0] : $result;
    }

    /**
     * Récupère une liste de valeurs indexée par une clé
     *
     * @param string $field Champ à récupérer
     * @param string $id Clé d'indexation (par défaut: 'id')
     * @return array Liste des valeurs indexée
     */
    public function lists(string $field, string $id = 'id'): array
    {
        $result = $this->get();
        return (new Collection($result))->lists($field, $id)->toArray();
    }

    /**
     * Exécute la requête SQL et retourne les résultats
     *
     * @return array Résultats de la requête
     */
    public function get(): array
    {
        // Vérification du cache
        if ($this->shouldUseCache()) {
            return $this->getFromCache();
        }

        // Exécution de la requête
        $result = $this->executeQuery();

        // Hydratation du modèle si nécessaire
        if ($this->model !== null) {
            $result = $this->hydrateModel($result);
        }

        // Mise en cache si activé
        if ($this->cache) {
            $this->storeInCache($result);
        }

        return $result;
    }

    /**
     * Vérifie si le cache doit être utilisé
     *
     * @return bool
     */
    private function shouldUseCache(): bool
    {
        return $this->cache && File::hasAndIsExpired(
            $this->getCachePath(),
            $this->cacheParams['ttl']
        );
    }

    /**
     * Récupère les données depuis le cache
     *
     * @return array
     * @throws CacheException Si le fichier de cache est inaccessible ou corrompu
     */
    private function getFromCache(): array
    {
        $cachePath = $this->getCachePath();
        
        if (!File::has($cachePath)) {
            throw CacheException::fileNotAccessible(
                $cachePath,
                "Le fichier de cache n'existe pas"
            );
        }
        
        $cacheContent = File::read($cachePath);
        
        if ($cacheContent === false) {
            throw CacheException::readError(
                $cachePath,
                "Impossible de lire le contenu du fichier de cache"
            );
        }
        
        $data = @unserialize($cacheContent);
        
        if ($data === false) {
            throw CacheException::readError(
                $cachePath,
                "Le contenu du fichier de cache est corrompu"
            );
        }
        
        return $data;
    }

    /**
     * Stocke les données dans le cache
     *
     * @param array $data Données à mettre en cache
     * @return void
     * @throws CacheException Si l'écriture dans le cache échoue
     */
    private function storeInCache(array $data): void
    {
        $cachePath = $this->getCachePath();
        $cacheDir = dirname($cachePath);
        
        // Vérifier que le répertoire de cache existe ou le créer
        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0777, true)) {
            throw CacheException::directoryNotAccessible(
                $cacheDir,
                "Impossible de créer le répertoire de cache"
            );
        }
        
        // Sérialiser les données et les écrire dans le fichier
        $serializedData = serialize($data);
        
        try {
            File::put($cachePath, $serializedData);
        } catch (\Exception $e) {
            throw CacheException::writeError(
                $cachePath,
                $e->getMessage()
            );
        }
    }

    /**
     * Retourne le chemin du fichier de cache
     *
     * @return string
     */
    private function getCachePath(): string
    {
        return Orm::getConfig('db.cache.path')
            . md5($this->cacheParams['id'])
            . $this->cachePrefix;
    }

    /**
     * Exécute la requête SQL et retourne les résultats
     *
     * @return array
     */
    private function executeQuery(): array
    {
        $stmt = $this->db->prepare($this->getSql());
        $bindParams = $this->getBindParams();
        if (count($bindParams) > 0) {
            $stmt->execute($bindParams);
        } else {
            $stmt->execute();
        }

        $result = $stmt->fetchAll();
        $stmt = null;

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
     * Hydrate le modèle avec les données de la base de données
     *
     * @param array $data Données à hydrater
     * @return array Collection d'objets hydratés
     * @throws ModelException Si le modèle n'est pas défini
     * @throws ModelException Si la classe du modèle n'existe pas
     */
    private function hydrateModel(array $data): array
    {
        if ($this->model === null) {
            throw new ModelException(
                'Le modèle doit être défini avant l\'hydratation',
                ModelException::PRIMARY_KEY_NOT_DEFINED
            );
        }

        if (!class_exists($this->model)) {
            throw new ModelException(
                sprintf('La classe du modèle "%s" n\'existe pas', $this->model),
                ModelException::MISSING_TARGET_MODEL,
                null,
                ['model_class' => $this->model]
            );
        }

        $result = [];
        $eagerLoadedRelations = $this->loadEagerRelations($data);

        foreach ($data as $dbColumn) {
            $entity = $this->createEntity($dbColumn);
            
            if (!empty($this->with)) {
                $this->attachEagerRelations($entity, $eagerLoadedRelations);
            }
            
            $result[] = $entity;
        }

        return $result;
    }

    /**
     * Crée une nouvelle instance du modèle et l'hydrate avec les données
     *
     * @param array|\stdClass $data Données à hydrater
     * @return object Instance du modèle hydratée
     */
    private function createEntity(array|\stdClass $data): object
    {
        $entity = new $this->model();
        
        if ($data instanceof \stdClass) {
            $data = (array) $data;
        }
        
        foreach ($data as $column => $value) {
            $entity->$column = $value;
        }
        
        return $entity;
    }

    /**
     * Charge les relations avec eager loading
     *
     * @param array $data Données principales
     * @return array Relations chargées
     */
    private function loadEagerRelations(array $data): array
    {
        if (empty($this->with)) {
            return [];
        }

        $params = [
            'results' => $data,
            'with' => $this->with
        ];

        return (new $this->model())->loadRelationsByEagerLoading($params);
    }

    /**
     * Attache les relations eager loaded à l'entité
     *
     * @param object $entity Entité à laquelle attacher les relations
     * @param array $relations Relations chargées
     * @return void
     */
    private function attachEagerRelations(object $entity, array $relations): void
    {
        $entity->getRelationByEagerLoading($relations, $this->with, $entity);
    }

    /**
     * Charge des relations avec eager loading
     *
     * @param string|array|null $relations Relations à charger
     * @return self
     */
    public function with(string|array|null $relations = null): static
    {
        if (is_string($relations)) {
            $this->with[] = $relations;
        } else {
            $this->with = array_unique($relations ?? []);
        }
        return $this;
    }

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
