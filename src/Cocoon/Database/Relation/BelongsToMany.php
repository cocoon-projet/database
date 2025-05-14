<?php
declare(strict_types=1);

namespace Cocoon\Database\Relation;

use Cocoon\Collection\Collection;
use Cocoon\Database\Query\Builder;
use Cocoon\Utilities\Strings;
use Cocoon\Database\Model;

/**
 * Classe BelongsToMany
 * Gère la relation "appartient à plusieurs" entre deux modèles.
 * Cette relation est utilisée pour les relations many-to-many.
 * Par exemple, un article peut appartenir à plusieurs catégories.
 *
 * @package Cocoon\Database\Relation
 */
class BelongsToMany
{
    /** @var Builder Instance du constructeur de requête */
    private Builder $query;

    /** @var Model Instance du modèle parent */
    private Model $model;

    /** @var string Nom de la clé étrangère pour le modèle lié */
    private string $keyTwo;

    /** @var string Nom de la clé étrangère pour le modèle parent */
    private string $keyOne;

    /** @var string Nom de la classe du modèle de référence (table pivot) */
    private string $refModel;

    /** @var string Nom de la classe du modèle lié */
    private string $related;

    /** @var array Paramètres pour le chargement avide */
    private array $eagerParams = [];

    /** @var array Clés uniques pour le chargement avide */
    private array $keys = [];

    /**
     * Constructeur de la relation BelongsToMany
     *
     * @param Model $model Le modèle parent
     * @param string $related Le nom de la classe du modèle lié
     * @param string $refModel Le nom de la classe du modèle de référence (table pivot)
     * @param string|null $keyOne La clé étrangère pour le modèle parent
     * @param string|null $keyTwo La clé étrangère pour le modèle lié
     */
    public function __construct(
        Model $model,
        string $related,
        string $refModel,
        ?string $keyOne = null,
        ?string $keyTwo = null
    ) {
        $this->query = Builder::init()->from($related::getTableName());
        $this->model = $model;
        $refModelTable = $refModel::getTableName();
        $this->related = $related;
        $this->refModel = $refModel;

        if ($keyOne) {
            $this->keyOne = $refModelTable . '.' . $keyOne;
        } else {
            $this->keyOne = $refModelTable . '.' . Strings::singular($model::getTableName()) . '_id';
        }

        if ($keyTwo) {
            $this->keyTwo = $refModelTable . '.' . $keyTwo;
        } else {
            $this->keyTwo = $refModelTable . '.' . Strings::singular($related::getTableName()) . '_id';
        }
    }

    /**
     * Définit les conditions pour le chargement paresseux
     * Configure la jointure et les conditions pour charger les relations
     */
    protected function lazyLoadingConditions(): void
    {
        $relatedRefTable = $this->related::getTableName();
        $this->query->select($this->keyOne . ' AS ' .
            Strings::singular($this->model::getTableName()) . '_id' . ',' . $relatedRefTable . '.*');
        $this->query->innerJoin($this->refModel::getTableName(), $this->keyTwo .
        ' = ' . $relatedRefTable . '.id');
        $id = $this->model->getPrimaryKey();
        $this->query->where($this->keyOne . ' = ?', $this->model->$id);
    }

    /**
     * Définit les paramètres pour le chargement avide
     *
     * @param array $params Les paramètres de chargement avide
     * @return self
     */
    public function setEagerParams(array $params): self
    {
        $this->eagerParams = $params;
        return $this;
    }

    /**
     * Définit les conditions pour le chargement avide
     * Configure la jointure et les conditions pour charger plusieurs relations
     */
    protected function eagerLoadingConditions(): void
    {
        $relatedRefTable = $this->related::getTableName();
        $this->query->select($this->keyOne . ' AS ' .
            Strings::singular($this->model::getTableName()) . '_id' . ',' . $relatedRefTable . '.*');
        $this->query->innerJoin($this->refModel::getTableName(), $this->keyTwo . ' = ' .
            $relatedRefTable . '.id');
        $this->query->in($this->keyOne, $this->getKeys());
    }

    /**
     * Récupère les clés uniques des résultats pour le chargement avide
     *
     * @return array
     */
    private function getKeys(): array
    {
        $this->keys = [];
        array_filter($this->eagerParams['results'], function ($getId) {
            $this->keys[] = $getId->id;
        });
        return array_unique($this->keys);
    }

    /**
     * Récupère la collection de résultats
     * Gère à la fois le chargement paresseux et le chargement avide
     *
     * @return Collection
     */
    public function getCollection(): Collection
    {
        if (isset($this->eagerParams['with'])) {
            $this->eagerLoadingConditions();
            $result = $this->query->get();
            return new Collection($result);
        }

        $this->query->setModel($this->related);
        $this->lazyLoadingConditions();
        $result = new Collection($this->query->get());
        return $result;
    }

    /**
     * Récupère les résultats par chargement avide
     * Associe les résultats à l'entité parente
     *
     * @param array|object $collection La collection de résultats
     * @param Model $entity L'entité parente
     * @return Collection
     */
    public function getByEagerloading($collection, Model $entity): Collection
    {
        $result = [];
        foreach ($collection as $collect) {
            $foreign = Strings::singular($this->model::getTableName()) . '_id';
            $id = $entity->getPrimaryKey();
            if ($collect->$foreign == $entity->$id) {
                $result[] = $this->hydrate($collect, $this->related);
            }
        }
        return new Collection($result);
    }

    /**
     * Hydrate un objet avec les données
     * Crée une nouvelle instance du modèle et lui assigne les données
     *
     * @param object $data Les données à hydrater
     * @param string $class La classe du modèle à instancier
     * @return Model
     */
    protected function hydrate(object $data, string $class): Model
    {
        $entity = new $class();
        foreach ((array)$data as $key => $value) {
            $entity->$key = $value;
        }
        return $entity;
    }
}
