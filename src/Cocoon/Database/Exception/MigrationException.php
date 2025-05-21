<?php
declare(strict_types=1);

namespace Cocoon\Database\Exception;

/**
 * Exception lancée pour les erreurs liées aux migrations
 */
class MigrationException extends DatabaseException
{
    /**
     * Code d'erreur par défaut pour les erreurs de migration
     */
    protected const DEFAULT_ERROR_CODE = 1800;
    
    /**
     * Code d'erreur pour un fichier de migration manquant
     */
    public const MISSING_MIGRATION_FILE = 1801;
    
    /**
     * Code d'erreur pour une classe de migration inexistante
     */
    public const MISSING_MIGRATION_CLASS = 1802;
    
    /**
     * Code d'erreur pour un chemin de migration invalide
     */
    public const INVALID_MIGRATION_PATH = 1803;
    
    /**
     * Code d'erreur pour une erreur d'exécution de migration
     */
    public const EXECUTION_FAILED = 1804;
    
    /**
     * Code d'erreur pour une opération non supportée
     */
    public const UNSUPPORTED_OPERATION = 1805;
    
    /**
     * Code d'erreur pour une erreur de schéma
     */
    public const SCHEMA_ERROR = 1806;
    
    /**
     * Code d'erreur pour une migration déjà appliquée
     */
    public const ALREADY_APPLIED = 1807;
    
    /**
     * Code d'erreur pour une opération reset ou fresh échouée
     */
    public const RESET_FRESH_FAILED = 1808;
    
    /**
     * Crée une exception pour un fichier de migration manquant
     *
     * @param string $migrationFile Le fichier de migration manquant
     * @return self
     */
    public static function missingFile(string $migrationFile): self
    {
        return new self(
            sprintf("Le fichier de migration '%s' est introuvable", $migrationFile),
            self::MISSING_MIGRATION_FILE,
            null,
            ['migration_file' => $migrationFile]
        );
    }
    
    /**
     * Crée une exception pour une classe de migration inexistante
     *
     * @param string $className Nom de la classe
     * @param string $migrationFile Fichier de migration
     * @return self
     */
    public static function missingClass(string $className, string $migrationFile): self
    {
        return new self(
            sprintf("La classe de migration '%s' est introuvable dans le fichier '%s'", $className, $migrationFile),
            self::MISSING_MIGRATION_CLASS,
            null,
            ['class_name' => $className, 'migration_file' => $migrationFile]
        );
    }
    
    /**
     * Crée une exception pour un chemin de migration invalide
     *
     * @param string $path Chemin invalide
     * @return self
     */
    public static function invalidPath(string $path): self
    {
        return new self(
            sprintf("Le chemin de migration '%s' n'est pas un répertoire valide", $path),
            self::INVALID_MIGRATION_PATH,
            null,
            ['path' => $path]
        );
    }
    
    /**
     * Crée une exception pour une erreur d'exécution de migration
     *
     * @param string $migration Nom de la migration
     * @param string $operation Opération (up ou down)
     * @param string $reason Raison de l'échec
     * @return self
     */
    public static function executionFailed(string $migration, string $operation, string $reason): self
    {
        return new self(
            sprintf("L'exécution de la migration '%s' (%s) a échoué : %s", $migration, $operation, $reason),
            self::EXECUTION_FAILED,
            null,
            ['migration' => $migration, 'operation' => $operation, 'reason' => $reason]
        );
    }
    
    /**
     * Crée une exception pour une opération non supportée dans le dialecte SQL
     *
     * @param string $operation Opération non supportée
     * @param string $driver Pilote SGBD
     * @return self
     */
    public static function unsupportedOperation(string $operation, string $driver): self
    {
        return new self(
            sprintf("L'opération '%s' n'est pas supportée par le pilote '%s'", $operation, $driver),
            self::UNSUPPORTED_OPERATION,
            null,
            ['operation' => $operation, 'driver' => $driver]
        );
    }
    
    /**
     * Crée une exception pour une erreur de définition de schéma
     *
     * @param string $table Table
     * @param string $reason Raison
     * @return self
     */
    public static function schemaError(string $table, string $reason): self
    {
        return new self(
            sprintf("Erreur de schéma pour la table '%s' : %s", $table, $reason),
            self::SCHEMA_ERROR,
            null,
            ['table' => $table, 'reason' => $reason]
        );
    }
    
    /**
     * Crée une exception pour une erreur lors d'une opération reset ou fresh
     *
     * @param string $operation Type d'opération (reset ou fresh)
     * @param string $reason Raison de l'échec
     * @return self
     */
    public static function resetFreshFailed(string $operation, string $reason): self
    {
        return new self(
            sprintf("L'opération '%s' a échoué : %s", $operation, $reason),
            self::RESET_FRESH_FAILED,
            null,
            ['operation' => $operation, 'reason' => $reason]
        );
    }
}
