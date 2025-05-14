<?php
declare(strict_types=1);

namespace Cocoon\Database\Exception;

/**
 * Exception lancée pour les erreurs liées aux requêtes SQL
 */
class QueryException extends DatabaseException
{
    /**
     * Code d'erreur par défaut pour les erreurs de requête
     */
    protected const DEFAULT_ERROR_CODE = 1300;
    
    /**
     * Code d'erreur pour une syntaxe SQL invalide
     */
    public const INVALID_SYNTAX = 1301;
    
    /**
     * Code d'erreur pour une opération non supportée
     */
    public const UNSUPPORTED_OPERATION = 1302;
    
    /**
     * Code d'erreur pour une erreur d'exécution de requête
     */
    public const EXECUTION_ERROR = 1303;
    
    /**
     * Code d'erreur pour un nom de colonne inexistant
     */
    public const COLUMN_NOT_FOUND = 1304;
    
    /**
     * Code d'erreur pour un paramètre invalide
     */
    public const INVALID_PARAMETER = 1305;
    
    /**
     * Requête SQL qui a causé l'exception
     * 
     * @var string|null
     */
    protected ?string $sqlQuery = null;
    
    /**
     * Paramètres de la requête
     * 
     * @var array|null
     */
    protected ?array $bindParams = null;
    
    /**
     * Constructeur
     *
     * @param string $message Message d'erreur
     * @param int|null $code Code d'erreur
     * @param \Throwable|null $previous Exception précédente
     * @param array $context Contexte supplémentaire
     * @param string|null $sqlQuery Requête SQL associée à l'erreur
     * @param array|null $bindParams Paramètres de la requête
     */
    public function __construct(
        string $message,
        ?int $code = null,
        ?\Throwable $previous = null,
        array $context = [],
        ?string $sqlQuery = null,
        ?array $bindParams = null
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->sqlQuery = $sqlQuery;
        $this->bindParams = $bindParams;
    }
    
    /**
     * Récupère la requête SQL associée à l'exception
     *
     * @return string|null
     */
    public function getSqlQuery(): ?string
    {
        return $this->sqlQuery;
    }
    
    /**
     * Récupère les paramètres de liaison associés à l'exception
     *
     * @return array|null
     */
    public function getBindParams(): ?array
    {
        return $this->bindParams;
    }
    
    /**
     * Crée une exception pour une erreur de syntaxe SQL
     *
     * @param string $sqlQuery Requête SQL
     * @param string $errorMessage Message d'erreur
     * @param array|null $bindParams Paramètres de la requête
     * @return self
     */
    public static function invalidSyntax(string $sqlQuery, string $errorMessage, ?array $bindParams = null): self
    {
        return new self(
            "Erreur de syntaxe SQL : $errorMessage",
            self::INVALID_SYNTAX,
            null,
            ['error_message' => $errorMessage],
            $sqlQuery,
            $bindParams
        );
    }
    
    /**
     * Crée une exception pour une erreur d'exécution de requête
     *
     * @param string $sqlQuery Requête SQL
     * @param string $errorMessage Message d'erreur
     * @param array|null $bindParams Paramètres de la requête
     * @return self
     */
    public static function executionError(string $sqlQuery, string $errorMessage, ?array $bindParams = null): self
    {
        return new self(
            "Erreur d'exécution de requête : $errorMessage",
            self::EXECUTION_ERROR,
            null,
            ['error_message' => $errorMessage],
            $sqlQuery,
            $bindParams
        );
    }
} 