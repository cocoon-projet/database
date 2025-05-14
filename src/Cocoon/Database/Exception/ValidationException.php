<?php
declare(strict_types=1);

namespace Cocoon\Database\Exception;

/**
 * Exception lancée pour les erreurs de validation de données
 */
class ValidationException extends DatabaseException
{
    /**
     * Code d'erreur par défaut pour les erreurs de validation
     */
    protected const DEFAULT_ERROR_CODE = 1700;
    
    /**
     * Code d'erreur pour une valeur obligatoire manquante
     */
    public const REQUIRED_VALUE_MISSING = 1701;
    
    /**
     * Code d'erreur pour un type de valeur invalide
     */
    public const INVALID_VALUE_TYPE = 1702;
    
    /**
     * Code d'erreur pour une valeur hors plage
     */
    public const VALUE_OUT_OF_RANGE = 1703;
    
    /**
     * Code d'erreur pour un format de valeur invalide
     */
    public const INVALID_VALUE_FORMAT = 1704;
    
    /**
     * Code d'erreur pour une règle de validation non respectée
     */
    public const VALIDATION_RULE_FAILED = 1705;
    
    /**
     * Liste des erreurs de validation
     *
     * @var array
     */
    protected array $errors = [];
    
    /**
     * Constructeur
     *
     * @param string $message Message d'erreur
     * @param int|null $code Code d'erreur
     * @param \Throwable|null $previous Exception précédente
     * @param array $context Contexte supplémentaire
     * @param array $errors Liste des erreurs de validation
     */
    public function __construct(
        string $message,
        ?int $code = null,
        ?\Throwable $previous = null,
        array $context = [],
        array $errors = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->errors = $errors;
    }
    
    /**
     * Récupère la liste des erreurs de validation
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Vérifie si une erreur existe pour un champ spécifique
     *
     * @param string $field Nom du champ
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }
    
    /**
     * Récupère les erreurs pour un champ spécifique
     *
     * @param string $field Nom du champ
     * @return array|null
     */
    public function getFieldErrors(string $field): ?array
    {
        return $this->errors[$field] ?? null;
    }
    
    /**
     * Crée une exception pour une erreur de validation avec plusieurs erreurs
     *
     * @param array $errors Liste des erreurs de validation
     * @return self
     */
    public static function withErrors(array $errors): self
    {
        return new self(
            "Des erreurs de validation sont survenues",
            self::DEFAULT_ERROR_CODE,
            null,
            [],
            $errors
        );
    }
    
    /**
     * Crée une exception pour une valeur obligatoire manquante
     *
     * @param string $field Nom du champ
     * @return self
     */
    public static function requiredValueMissing(string $field): self
    {
        $errors = [$field => ['La valeur est obligatoire']];
        return new self(
            "Le champ '$field' est obligatoire",
            self::REQUIRED_VALUE_MISSING,
            null,
            ['field' => $field],
            $errors
        );
    }
    
    /**
     * Crée une exception pour un type de valeur invalide
     *
     * @param string $field Nom du champ
     * @param string $expectedType Type attendu
     * @param string $actualType Type reçu
     * @return self
     */
    public static function invalidValueType(string $field, string $expectedType, string $actualType): self
    {
        $errors = [$field => ["Type attendu : $expectedType, type reçu : $actualType"]];
        return new self(
            "Le champ '$field' doit être de type '$expectedType', '$actualType' reçu",
            self::INVALID_VALUE_TYPE,
            null,
            ['field' => $field, 'expected_type' => $expectedType, 'actual_type' => $actualType],
            $errors
        );
    }
} 