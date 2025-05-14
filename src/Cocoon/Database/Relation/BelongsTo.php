<?php
declare(strict_types=1);

namespace Cocoon\Database\Relation;

use Cocoon\Database\Query\Builder;
use Cocoon\Utilities\Strings;
use Cocoon\Database\Model;

/**
 * Classe BelongsTo
 * Gère la relation "appartient à" entre deux modèles.
 * Cette relation est utilisée lorsqu'un modèle appartient à un autre modèle.
 * Par exemple, un article appartient à un utilisateur.
 *
 * @package Cocoon\Database\Relation
 */
class BelongsTo
{
    /** @var Builder Instance du constructeur de requête */
    private Builder $query;

    /** @var string Nom de la classe du modèle lié */
    private string $related;

    /** @var string Nom de la clé étrangère */
    private string $foreignKey;

    /** @var string Nom de la clé locale */
    private string $localKey;

    /** @var Model Instance du modèle parent */
    private Model $model;

    /** @var array Paramètres pour le chargement avide */
    private array $eagerParams = [];

    /** @var array Clés uniques pour le chargement avide */
    private array $keys = [];

    /**
     * Constructeur de la relation BelongsTo
     *
     * @param Model $model Le modèle enfant
     * @param string $related Le nom de la classe du modèle parent
     * @param string|null $foreignKey La clé étrangère (par défaut: clé primaire du modèle)
     * @param string|null $localKey La clé locale (par défaut: nom_singulier_id)
     */
    public function __construct(Model $model, string $related, ?string $foreignKey = null, ?string $localKey = null)
    {
        $this->query = Builder::init()->from($related::getTableName());
        $this->model = $model;
        $this->related = $related;
        $this->foreignKey = $foreignKey ?? $model->getPrimaryKey();
        $this->localKey = $localKey ?? Strings::singular($related::getTableName()) . '_id';
    }

    /**
     * Définit les conditions pour le chargement paresseux
     * Utilise la clé locale du modèle pour trouver l'enregistrement lié
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
     * Utilise les clés uniques pour charger plusieurs enregistrements liés
     */
    protected function eagerLoadingConditions(): void
    {
        $this->query->in($this->foreignKey, $this->getKeys());
    }

    /**
     * Sélectionne des champs spécifiques dans la requête
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
     * Récupère les clés uniques des résultats pour le chargement avide
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
     * Récupère la collection de résultats
     * Gère à la fois le chargement paresseux et le chargement avide
     *
     * @return mixed
     */
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

    /**
     * Récupère les résultats par chargement avide
     * Associe les résultats à l'entité parente
     *
     * @param mixed $collection La collection de résultats
     * @param Model $entity L'entité parente
     * @return mixed
     */
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

    /**
     * Hydrate un objet avec les données
     * Crée une nouvelle instance du modèle et lui assigne les données
     *
     * @param mixed $data Les données à hydrater
     * @param string $class La classe du modèle à instancier
     * @return Model
     */
    protected function hydrate($data, $class)
    {
        $entity = new $class();
        foreach ($data as $key => $value) {
            $entity->$key = $value;
        }
        return $entity;
    }
}
