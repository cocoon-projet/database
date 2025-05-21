<?php
declare(strict_types=1);

namespace Cocoon\Database\Traits;

use Cocoon\Utilities\Strings;

/**
 * Trait MutatorAccessorTrait
 * Gère les mutateurs et accesseurs pour les modèles.
 * Permet de transformer les données lors de leur lecture/écriture.
 * Les mutateurs sont des méthodes qui transforment les données avant leur stockage.
 * Les accesseurs sont des méthodes qui transforment les données lors de leur lecture.
 *
 * @package Cocoon\Database\Traits
 */
trait MutatorAccessorTrait
{
    /**
     * Données du modèle.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Suffixe utilisé pour les méthodes de mutateur et d'accesseur.
     *
     * @var string
     */
    protected string $suffix = 'Model';

    /**
     * Cache des mutateurs et accesseurs.
     *
     * @var array<string, string>
     */
    protected static array $cachingMutatorAndAccessor = [];

    /**
     * Définit une donnée pour le modèle.
     * Applique le mutateur si celui-ci existe.
     *
     * @param string $name Nom de la propriété
     * @param mixed $value Valeur à définir
     */
    public function setData(string $name, mixed $value): void
    {
        if ($this->hasMutator($name)) {
            $mutator = static::$cachingMutatorAndAccessor['set_' . $name];
            $this->data[$name] = $this->$mutator($value);
        } else {
            $this->data[$name] = $value;
        }
    }

    /**
     * Retourne une donnée du modèle.
     * Applique l'accesseur si celui-ci existe.
     *
     * @param string $name Nom de la propriété
     * @return mixed Valeur de la propriété
     * @throws \ReflectionException
     */
    public function getData(string $name): mixed
    {
        if ($this->hasAccessor($name)) {
            $accessor = static::$cachingMutatorAndAccessor['get_' . $name];
            return $this->$accessor($this->data[$name]);
        }

        if ($this->isDateTime($name) && isset($this->data[$name])) {
           return $this->data[$name];
        }

        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        if (isset($this->relations()[$name])) {
            return $this->relations()[$name]->getCollection();
        }

        return null;
    }

    /**
     * Détermine si le champ est de type date.
     *
     * @param string $key Nom du champ
     * @return bool True si le champ est une date
     */
    protected function isDateTime(string $key): bool
    {
        $defaults = ['created_at', 'updated_at'];
        $fields_mutator = array_unique(array_merge($this->dates, $defaults));
        return in_array($key, $fields_mutator);
    }

    /**
     * Détermine si un mutateur existe pour la propriété donnée.
     *
     * @param string $name Nom de la propriété
     * @return bool True si un mutateur existe
     */
    public function hasMutator(string $name): bool
    {
        $mutatorName = 'set' . Strings::camelize($name) . $this->suffix;
        if (method_exists($this, $mutatorName)) {
            static::$cachingMutatorAndAccessor['set_' . $name] = $mutatorName;
            return true;
        }
        return false;
    }

    /**
     * Détermine si un accesseur existe pour la propriété donnée.
     *
     * @param string $name Nom de la propriété
     * @return bool True si un accesseur existe
     */
    public function hasAccessor(string $name): bool
    {
        $accessorName = 'get' . Strings::camelize($name) . $this->suffix;
        if (method_exists($this, $accessorName)) {
            static::$cachingMutatorAndAccessor['get_' . $name] = $accessorName;
            return true;
        }
        return false;
    }

    /**
     * Définit le suffixe pour les mutateurs et accesseurs.
     *
     * @param string $suffix Nouveau suffixe
     */
    public function setSuffixMutator(string $suffix): void
    {
        $this->suffix = $suffix;
    }

    /**
     * Retourne toutes les données de l'entité.
     *
     * @return array<string, mixed> Données de l'entité
     */
    public function getEntityData(): array
    {
        return $this->data;
    }
    /**
     * Retourne toutes les données de l'entité.
     *
     * @return array<string, mixed> Données de l'entité
     */
    public function toArray(): array
    {
        return $this->getEntityData();
    }
}
