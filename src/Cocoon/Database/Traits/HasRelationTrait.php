<?php
declare(strict_types=1);

namespace Cocoon\Database\Traits;

use Cocoon\Database\Relation\HasOne;
use Cocoon\Database\Relation\HasMany;
use Cocoon\Database\Relation\BelongsTo;
use Cocoon\Database\Relation\BelongsToMany;
use Cocoon\Database\Model;

/**
 * Trait HasRelationTrait
 * Fournit les méthodes pour gérer les relations entre les modèles.
 * Ce trait est utilisé dans les modèles pour définir et gérer les relations.
 *
 * @package Cocoon\Database\Traits
 */
trait HasRelationTrait
{
    /**
     * Définit une relation "a un" (has one)
     * Par exemple, un utilisateur a un profil
     *
     * @param string $related Le nom de la classe du modèle lié
     * @param string|null $foreignKey La clé étrangère
     * @param string|null $localKey La clé locale
     * @return HasOne
     */
    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        return new HasOne($this, $related, $foreignKey, $localKey);
    }

    /**
     * Définit une relation "a plusieurs" (has many)
     * Par exemple, un utilisateur a plusieurs articles
     *
     * @param string $related Le nom de la classe du modèle lié
     * @param string|null $foreignKey La clé étrangère
     * @param string|null $localKey La clé locale
     * @return HasMany
     */
    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        return new HasMany($this, $related, $foreignKey, $localKey);
    }

    /**
     * Définit une relation "appartient à" (belongs to)
     * Par exemple, un article appartient à un utilisateur
     *
     * @param string $related Le nom de la classe du modèle lié
     * @param string|null $foreignKey La clé étrangère
     * @param string|null $localKey La clé locale
     * @return BelongsTo
     */
    public function belongsTo(string $related, ?string $foreignKey = null, ?string $localKey = null): BelongsTo
    {
        return new BelongsTo($this, $related, $foreignKey, $localKey);
    }

    /**
     * Définit une relation "appartient à plusieurs" (belongs to many)
     * Par exemple, un article appartient à plusieurs catégories
     *
     * @param string $related Le nom de la classe du modèle lié
     * @param string $refModel Le nom de la classe du modèle de référence (table pivot)
     * @param string|null $keyOne La clé étrangère pour le modèle parent
     * @param string|null $keyTwo La clé étrangère pour le modèle lié
     * @return BelongsToMany
     */
    public function belongsToMany(
        string $related,
        string $refModel,
        ?string $keyOne = null,
        ?string $keyTwo = null
    ): BelongsToMany {
        return new BelongsToMany($this, $related, $refModel, $keyOne, $keyTwo);
    }

    /**
     * Charge les relations par chargement avide
     * Récupère toutes les relations demandées en une seule requête
     *
     * @param array $params Les paramètres de chargement avide
     * @return array
     */
    public function loadRelationsByEagerLoading(array $params): array
    {
        $collect = [];
        foreach ($params['with'] as $with) {
            if (isset($this->relations()[$with])) {
                $collect[$with] = $this->relations()[$with]->setEagerParams($params)->getCollection();
            }
        }
        return $collect;
    }

    /**
     * Récupère les relations par chargement avide
     * Associe les résultats aux entités parentes
     *
     * @param array $collect La collection de résultats
     * @param array $withs Les noms des relations à charger
     * @param Model $entity L'entité parente
     */
    public function getRelationByEagerLoading(array $collect, array $withs, Model $entity): void
    {
        foreach ($withs as $with) {
            if (isset($this->relations()[$with])) {
                $this->$with = $this->relations()[$with]->getByEagerloading($collect[$with], $entity);
            }
        }
    }
}
