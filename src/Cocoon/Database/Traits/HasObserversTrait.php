<?php
declare(strict_types=1);

namespace Cocoon\Database\Traits;

use Cocoon\Database\Model;

/**
 * Trait HasObserversTrait
 * Fournit un système d'observateurs pour les modèles.
 * Permet d'exécuter du code avant/après les opérations CRUD.
 * Les observateurs peuvent être définis soit via des méthodes statiques,
 * soit via une classe d'observateur dédiée.
 *
 * @package Cocoon\Database\Traits
 */
trait HasObserversTrait
{
    /** @var array Tableau des observateurs définis via des méthodes statiques */
    protected static array $observers = [];

    /** @var string|null Classe d'observateur dédiée */
    protected static ?string $observerClass = null;

    /**
     * Définit un observateur pour l'événement "avant sauvegarde"
     *
     * @param callable $callback Fonction à exécuter avant la sauvegarde
     */
    public static function beforeSave(callable $callback): void
    {
        static::$observers['beforeSave'] = $callback;
    }

    /**
     * Définit un observateur pour l'événement "après sauvegarde"
     *
     * @param callable $callback Fonction à exécuter après la sauvegarde
     */
    public static function afterSave(callable $callback): void
    {
        static::$observers['afterSave'] = $callback;
    }

    /**
     * Définit un observateur pour l'événement "avant mise à jour"
     *
     * @param callable $callback Fonction à exécuter avant la mise à jour
     */
    public static function beforeUpdate(callable $callback): void
    {
        static::$observers['beforeUpdate'] = $callback;
    }

    /**
     * Définit un observateur pour l'événement "après mise à jour"
     *
     * @param callable $callback Fonction à exécuter après la mise à jour
     */
    public static function afterUpdate(callable $callback): void
    {
        static::$observers['afterUpdate'] = $callback;
    }

    /**
     * Définit un observateur pour l'événement "avant suppression"
     *
     * @param callable $callback Fonction à exécuter avant la suppression
     */
    public static function beforeDelete(callable $callback): void
    {
        static::$observers['beforeDelete'] = $callback;
    }

    /**
     * Définit un observateur pour l'événement "après suppression"
     *
     * @param callable $callback Fonction à exécuter après la suppression
     */
    public static function afterDelete(callable $callback): void
    {
        static::$observers['afterDelete'] = $callback;
    }

    /**
     * Exécute l'observateur pour un événement donné
     * Vérifie d'abord les observateurs statiques, puis la classe d'observateur
     *
     * @param string $observerName Nom de l'événement à observer
     */
    protected function runObserver(string $observerName): void
    {
        // Exécute l'observateur statique s'il existe
        if (isset(static::$observers[$observerName])) {
            static::$observers[$observerName]($this);
        }

        // Exécute la méthode de la classe d'observateur si elle existe
        if (static::$observerClass !== null) {
            if (method_exists(static::$observerClass, $observerName)) {
                $observer = static::$observerClass;
                (new $observer())->$observerName($this);
            }
        }
    }

    /**
     * Définit une classe d'observateur dédiée
     * Cette classe doit implémenter les méthodes d'observateur correspondantes
     *
     * @param string $class Nom de la classe d'observateur
     */
    public static function observer(string $class): void
    {
        static::$observerClass = $class;
    }
}
