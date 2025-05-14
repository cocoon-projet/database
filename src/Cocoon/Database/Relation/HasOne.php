<?php
declare(strict_types=1);

namespace Cocoon\Database\Relation;

use Cocoon\Database\Query\Builder;
use Cocoon\Utilities\Strings;
use Cocoon\Database\Model;

/**
 * Classe HasOne
 * Gère la relation "a un" entre deux modèles
 */
class HasOne
{
    private Builder $query;
    private string $related;
    private string $foreignKey;
    private string $localKey;
    private Model $model;
    private array $eagerParams = [];
    private array $keys = [];

    /**
     * Constructeur de la relation HasOne
     *
     * @param Model $model Le modèle parent
     * @param string $related Le nom de la classe du modèle lié
     * @param string|null $foreignKey La clé étrangère
     * @param string|null $localKey La clé locale
     */
    public function __construct(
        Model $model,
        string $related,
        ?string $foreignKey = null,
        ?string $localKey = null
    ) {
        $this->query = Builder::init()->from($related::getTableName());
        $this->model = $model;
        $this->related = $related;
        $this->foreignKey = $foreignKey ?? Strings::singular($model::getTableName()) . '_id';
        $this->localKey = $localKey ?? $model->getPrimaryKey();
    }

    /**
     * Définit les conditions pour le chargement paresseux
     */
    protected function lazyLoadingConditions(): void
    {
        $local = $this->localKey;
        $this->query->where($this->foreignKey . ' = ?', $this->model->$local);
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
     */
    protected function eagerLoadingConditions(): void
    {
        $this->query->in($this->foreignKey, $this->getKeys());
    }

    /**
     * Récupère les clés uniques des résultats
     *
     * @return array
     */
    private function getKeys(): array
    {
        $local = $this->localKey;
        $this->keys = [];
        
        array_filter($this->eagerParams['results'], function ($getId) use ($local) {
            $this->keys[] = $getId->$local;
        });
        
        return array_unique($this->keys);
    }

    /**
     * Sélectionne des champs spécifiques
     *
     * @param string $fields Les champs à sélectionner
     * @return self
     */
    public function select(string $fields): self
    {
        $value = $this->foreignKey . ',' . $fields;
        $this->query->select($value);
        return $this;
    }

    /**
     * Récupère la collection de résultats
     *
     * @return object|null
     */
    public function getCollection(): ?object
    {
        if (isset($this->eagerParams['with'])) {
            $this->eagerLoadingConditions();
            $result = $this->query->get();
            return $result[0] ?? null;
        }

        $this->query->setModel($this->related);
        $this->lazyLoadingConditions();
        $total = $this->query->get();
        
        if (empty($total)) {
            return null;
        }

        return is_array($total) ? $total[0] : $total;
    }

    /**
     * Récupère les résultats par chargement avide
     *
     * @param array|object $collection La collection de résultats
     * @param Model $entity L'entité parente
     * @return Model|null
     */
    public function getByEagerloading(array|object $collection, Model $entity): ?Model
    {
        if (is_object($collection)) {
            $collection = [$collection];
        }

        $result = [];
        foreach ($collection as $collect) {
            $foreign = $this->foreignKey;
            $local = $this->localKey;
            if ($collect->$foreign == $entity->$local) {
                $result[] = $this->hydrate($collect, $this->related);
            }
        }
        return $result[0] ?? null;
    }

    /**
     * Hydrate un objet avec les données
     *
     * @param object $data Les données à hydrater
     * @param string $class La classe à instancier
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
