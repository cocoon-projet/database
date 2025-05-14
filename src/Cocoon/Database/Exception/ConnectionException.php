<?php
declare(strict_types=1);

namespace Cocoon\Database\Exception;

use RuntimeException;

/**
 * Exception lancée pour les erreurs de connexion à la base de données
 */
class ConnectionException extends DatabaseException
{
    /**
     * Code d'erreur par défaut pour les erreurs de connexion
     */
    protected const DEFAULT_ERROR_CODE = 1100;
    
    /**
     * Code d'erreur pour une connexion inexistante
     */
    public const CONNECTION_NOT_FOUND = 1101;
    
    /**
     * Code d'erreur pour une erreur de configuration de la connexion
     */
    public const INVALID_CONFIGURATION = 1102;
    
    /**
     * Code d'erreur pour un échec de connexion au serveur
     */
    public const CONNECTION_FAILED = 1103;
    
    /**
     * Code d'erreur pour une base de données inexistante
     */
    public const DATABASE_NOT_FOUND = 1104;
    
    /**
     * Crée une exception pour une connexion inexistante
     *
     * @param string $connectionName Nom de la connexion
     * @return self
     */
    public static function connectionNotFound(string $connectionName): self
    {
        return new self(
            "La connexion '$connectionName' n'existe pas",
            self::CONNECTION_NOT_FOUND,
            null,
            ['connection_name' => $connectionName]
        );
    }
    
    /**
     * Crée une exception pour une configuration de connexion invalide
     *
     * @param string $reason Raison de l'invalidité
     * @return self
     */
    public static function invalidConfiguration(string $reason): self
    {
        return new self(
            "Configuration de connexion invalide : $reason",
            self::INVALID_CONFIGURATION
        );
    }
}
