<?php
declare(strict_types=1);

namespace Cocoon\Database\Exception;

/**
 * Exception lancée pour les erreurs liées au système de cache
 */
class CacheException extends DatabaseException
{
    /**
     * Code d'erreur par défaut pour les erreurs de cache
     */
    protected const DEFAULT_ERROR_CODE = 1600;
    
    /**
     * Code d'erreur pour un fichier de cache non accessible
     */
    public const CACHE_FILE_NOT_ACCESSIBLE = 1601;
    
    /**
     * Code d'erreur pour une erreur d'écriture de cache
     */
    public const CACHE_WRITE_ERROR = 1602;
    
    /**
     * Code d'erreur pour une erreur de lecture de cache
     */
    public const CACHE_READ_ERROR = 1603;
    
    /**
     * Code d'erreur pour une erreur de suppression de cache
     */
    public const CACHE_DELETE_ERROR = 1604;
    
    /**
     * Code d'erreur pour un répertoire de cache non accessible
     */
    public const CACHE_DIRECTORY_NOT_ACCESSIBLE = 1605;
    
    /**
     * Code d'erreur pour un identifiant de cache invalide
     */
    public const INVALID_CACHE_ID = 1606;
    
    /**
     * Crée une exception pour un fichier de cache non accessible
     *
     * @param string $filePath Chemin du fichier
     * @param string $reason Raison
     * @return self
     */
    public static function fileNotAccessible(string $filePath, string $reason): self
    {
        return new self(
            "Impossible d'accéder au fichier de cache '$filePath' : $reason",
            self::CACHE_FILE_NOT_ACCESSIBLE,
            null,
            ['file_path' => $filePath, 'reason' => $reason]
        );
    }
    
    /**
     * Crée une exception pour une erreur d'écriture de cache
     *
     * @param string $filePath Chemin du fichier
     * @param string $reason Raison
     * @return self
     */
    public static function writeError(string $filePath, string $reason): self
    {
        return new self(
            "Erreur lors de l'écriture du fichier de cache '$filePath' : $reason",
            self::CACHE_WRITE_ERROR,
            null,
            ['file_path' => $filePath, 'reason' => $reason]
        );
    }
    
    /**
     * Crée une exception pour une erreur de lecture de cache
     *
     * @param string $filePath Chemin du fichier
     * @param string $reason Raison
     * @return self
     */
    public static function readError(string $filePath, string $reason): self
    {
        return new self(
            "Erreur lors de la lecture du fichier de cache '$filePath' : $reason",
            self::CACHE_READ_ERROR,
            null,
            ['file_path' => $filePath, 'reason' => $reason]
        );
    }
    
    /**
     * Crée une exception pour un répertoire de cache non accessible
     *
     * @param string $directory Chemin du répertoire
     * @param string $reason Raison
     * @return self
     */
    public static function directoryNotAccessible(string $directory, string $reason): self
    {
        return new self(
            "Impossible d'accéder au répertoire de cache '$directory' : $reason",
            self::CACHE_DIRECTORY_NOT_ACCESSIBLE,
            null,
            ['directory' => $directory, 'reason' => $reason]
        );
    }
} 