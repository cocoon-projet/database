<?php
declare(strict_types=1);

namespace Cocoon\Database;

use Cocoon\Utilities\Strings;

/**
 * Gestion des Mutators et Accessor
 */
trait MutatorAccessorTrait
{

    /**
     *  Données du model.
     *
     * @var array
     */
    protected $data = [];
    protected $suffix = 'Model';

    /**
     * Caching des mutators et accessor;
     *
     * @var array
     */
    protected static $cachingMutatorAndAccessor = [];

    /**
     * Definit une donnée pour le model
     *
     * @param string $name
     * @param mixed $value
     */
    public function setData($name, $value)
    {
        if ($this->hasMutator($name)) {
            $mutator = static::$cachingMutatorAndAccessor['set_' . $name];
            $this->data[$name] = $this->$mutator($value);
        } else {
            $this->data[$name] = $value;
        }
    }

    /**
     * Retourne une donnée du model
     *
     * @param string $name
     * @return mixed
     * @throws \ReflectionException
     */
    public function getData($name)
    {

        if ($this->hasAccessor($name)) {
            $accessor = static::$cachingMutatorAndAccessor['get_' . $name];
            return $this->$accessor($this->data[$name]);
        }
        if ($this->isDateTime($name)) {
            return datetime($this->data[$name]);
        }

        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        if (isset($this->relations()[$name])) {
            return $this->relations()[$name]->getCollection();
        }
    }

    /**
     * Détermine si le champ est de type date
     *
     * @param string $key
     * @return boo!
     */
    protected function isDateTime($key)
    {
        $defaults = ['created_at', 'updated_at'];
        $fields_mutator = array_unique(array_merge($this->dates, $defaults));
        return in_array($key, $fields_mutator);
    }

    /**
     * Détermine si un mutator existe
     *
     * @param string $name
     * @return bool
     */
    public function hasMutator($name)
    {
        if (method_exists($this, 'set' . Strings::camelize($name) . $this->suffix)) {
            static::$cachingMutatorAndAccessor['set_' . $name] = 'set' . Strings::camelize($name) . $this->suffix;
            return true;
        }
        return false;
    }

    /**
     * Détermine si un accessor existe
     * @param string $name
     * @return bool
     */
    public function hasAccessor($name)
    {
        if (method_exists($this, 'get' . Strings::camelize($name) . $this->suffix)) {
            static::$cachingMutatorAndAccessor['get_' . $name] = 'get' . Strings::camelize($name) . $this->suffix;
            return true;
        }
        return false;
    }

    /**
     * Determine le suffixe pour les mutators et accessors
     *
     * @param string $suffix
     */
    public function setSuffixMutator($suffix)
    {
        $this->suffix = $suffix;
    }

    /**
     * retounne les donnés d'une ligne de l'entity
     * @return array
     */
    public function getEntityData() :array
    {
        return $this->data;
    }
}
