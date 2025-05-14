<?php
declare(strict_types=1);

namespace Cocoon\Database\Exception;

/**
 * Exception lancée pour les erreurs liées aux modèles
 */
class ModelException extends DatabaseException
{
    /**
     * Code d'erreur par défaut pour les erreurs de modèle
     */
    protected const DEFAULT_ERROR_CODE = 1200;
    
    /**
     * Code d'erreur pour une méthode inexistante
     */
    public const METHOD_NOT_FOUND = 1201;
    
    /**
     * Code d'erreur pour une propriété inexistante
     */
    public const PROPERTY_NOT_FOUND = 1202;
    
    /**
     * Code d'erreur pour une relation inexistante
     */
    public const RELATION_NOT_FOUND = 1203;
    
    /**
     * Code d'erreur pour une table non définie
     */
    public const TABLE_NOT_DEFINED = 1204;
    
    /**
     * Code d'erreur pour une clé primaire non définie
     */
    public const PRIMARY_KEY_NOT_DEFINED = 1205;
    
    /**
     * Code d'erreur pour un modèle cible inexistant
     */
    public const MISSING_TARGET_MODEL = 1206;
    
    /**
     * Crée une exception pour une méthode inexistante
     *
     * @param string $method Nom de la méthode
     * @param string $model Nom du modèle
     * @return self
     */
    public static function methodNotFound(string $method, string $model): self
    {
        return new self(
            "La méthode '$method' n'existe pas dans le modèle '$model'",
            self::METHOD_NOT_FOUND,
            null,
            ['method' => $method, 'model' => $model]
        );
    }
    
    /**
     * Crée une exception pour une propriété inexistante
     *
     * @param string $property Nom de la propriété
     * @param string $model Nom du modèle
     * @return self
     */
    public static function propertyNotFound(string $property, string $model): self
    {
        return new self(
            "La propriété '$property' n'existe pas dans le modèle '$model'",
            self::PROPERTY_NOT_FOUND,
            null,
            ['property' => $property, 'model' => $model]
        );
    }
    
    /**
     * Crée une exception pour une relation inexistante
     *
     * @param string $relation Nom de la relation
     * @param string $model Nom du modèle
     * @return self
     */
    public static function relationNotFound(string $relation, string $model): self
    {
        return new self(
            "La relation '$relation' n'existe pas dans le modèle '$model'",
            self::RELATION_NOT_FOUND,
            null,
            ['relation' => $relation, 'model' => $model]
        );
    }
}
