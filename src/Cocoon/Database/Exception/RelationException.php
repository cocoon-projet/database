<?php
declare(strict_types=1);

namespace Cocoon\Database\Exception;

/**
 * Exception lancée pour les erreurs liées aux relations entre modèles
 */
class RelationException extends DatabaseException
{
    /**
     * Code d'erreur par défaut pour les erreurs de relation
     */
    protected const DEFAULT_ERROR_CODE = 1500;
    
    /**
     * Code d'erreur pour un type de relation invalide
     */
    public const INVALID_RELATION_TYPE = 1501;
    
    /**
     * Code d'erreur pour une configuration de relation manquante
     */
    public const MISSING_RELATION_CONFIG = 1502;
    
    /**
     * Code d'erreur pour une définition de clé étrangère manquante
     */
    public const MISSING_FOREIGN_KEY = 1503;
    
    /**
     * Code d'erreur pour une définition de clé locale manquante
     */
    public const MISSING_LOCAL_KEY = 1504;
    
    /**
     * Code d'erreur pour un modèle cible manquant
     */
    public const MISSING_TARGET_MODEL = 1505;
    
    /**
     * Code d'erreur pour une erreur de chargement de relation
     */
    public const LOADING_RELATION_ERROR = 1506;
    
    /**
     * Crée une exception pour un type de relation invalide
     *
     * @param string $relationType Type de relation demandé
     * @param array $validTypes Types de relation valides
     * @return self
     */
    public static function invalidType(string $relationType, array $validTypes): self
    {
        return new self(
            "Type de relation invalide : '$relationType'. Types valides : " . implode(', ', $validTypes),
            self::INVALID_RELATION_TYPE,
            null,
            ['relation_type' => $relationType, 'valid_types' => $validTypes]
        );
    }
    
    /**
     * Crée une exception pour une configuration de relation manquante
     *
     * @param string $relationName Nom de la relation
     * @param string $modelClass Nom de la classe du modèle
     * @return self
     */
    public static function missingConfiguration(string $relationName, string $modelClass): self
    {
        return new self(
            "Configuration manquante pour la relation '$relationName' dans le modèle '$modelClass'",
            self::MISSING_RELATION_CONFIG,
            null,
            ['relation_name' => $relationName, 'model_class' => $modelClass]
        );
    }
    
    /**
     * Crée une exception pour une clé étrangère manquante
     *
     * @param string $relationName Nom de la relation
     * @param string $modelClass Nom de la classe du modèle
     * @return self
     */
    public static function missingForeignKey(string $relationName, string $modelClass): self
    {
        return new self(
            "Clé étrangère manquante pour la relation '$relationName' dans le modèle '$modelClass'",
            self::MISSING_FOREIGN_KEY,
            null,
            ['relation_name' => $relationName, 'model_class' => $modelClass]
        );
    }
    
    /**
     * Crée une exception pour un modèle cible manquant
     *
     * @param string $modelClass Nom de la classe du modèle cible
     * @param string $relationName Nom de la relation
     * @return self
     */
    public static function missingTargetModel(string $modelClass, string $relationName): self
    {
        return new self(
            "Le modèle cible '$modelClass' pour la relation '$relationName' n'existe pas",
            self::MISSING_TARGET_MODEL,
            null,
            ['model_class' => $modelClass, 'relation_name' => $relationName]
        );
    }
}
