<?php
declare(strict_types=1);

namespace Cocoon\Database\Relation;

use Cocoon\Database\Orm;
use Cocoon\Pager\Paginator;
use Cocoon\Utilities\Strings;
use Cocoon\Collection\Collection;
use Cocoon\Pager\PaginatorConfig;
use Cocoon\Database\Query\Builder;
use Cocoon\Database\Model;

/**
 * Classe HasMany
 * Gère la relation "a plusieurs" entre deux modèles
 */
class HasMany
{
    private Builder $query;
    private Model $model;
    private string $related;
    private string $foreignKey;
    private string $localKey;
    private array $eagerParams = [];
    private array $keys = [];
    private bool $paginationRelated = false;
    private array $paginationRelatedOptions = [];

    /**
     * Constructeur de la relation HasMany
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
     * Active la pagination pour les relations
     *
     * @param int $perpage Nombre d'éléments par page
     * @param string|null $mode Mode de style de pagination
     * @return self
     */
    public function paginate(int $perpage = 1, ?string $mode = null): self
    {
        $this->paginationRelated = true;
        $this->paginationRelatedOptions = [
            'styling' => $mode ?? 'all',
            'perpage' => $perpage
        ];
        
        return $this;
    }

    /**
     * Récupère la collection de résultats
     *
     * @return Collection|Paginator
     * @throws \Exception Si la pagination est activée en lazy loading
     */
    public function getCollection(): Collection|Paginator
    {
        if (isset($this->eagerParams['with'])) {
            $this->eagerLoadingConditions();
            $result = $this->query->get();
            return new Collection($result);
        } else {
            if ($this->paginationRelated) {
                throw new \Exception('La fonctionnalité de pagination n\'est pas disponible en lazy loading');
            }
            $this->query->setModel($this->related);
            $this->lazyLoadingConditions();
            return new Collection($this->query->get());
        }
    }

    /**
     * Récupère les résultats par chargement avide
     *
     * @param Collection $collection La collection de résultats
     * @param Model $entity L'entité parente
     * @return Collection|Paginator
     */
    public function getByEagerloading(Collection $collection, Model $entity): Collection|Paginator
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
            return $this->createPaginator($result);
        }

        return new Collection($result);
    }

    /**
     * Crée une instance de Paginator
     *
     * @param array $result Les résultats à paginer
     * @return Paginator
     */
    private function createPaginator(array $result): Paginator
    {
        $count = count($result);
        $items = $count ? $result : new Collection([]);
        
        $config = new PaginatorConfig($items, $count);
        $config->setPerPage($this->paginationRelatedOptions['perpage']);
        $config->setStyling($this->paginationRelatedOptions['styling']);
        $config->setCssFramework(Orm::getConfig('pagination.renderer'));
        
        return new Paginator($config);
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
        foreach ($data as $key => $value) {
            $entity->$key = $value;
        }
        return $entity;
    }
}
