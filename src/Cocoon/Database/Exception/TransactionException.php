<?php
declare(strict_types=1);

namespace Cocoon\Database\Exception;

/**
 * Exception lancée pour les erreurs liées aux transactions
 */
class TransactionException extends DatabaseException
{
    /**
     * Code d'erreur par défaut pour les erreurs de transaction
     */
    protected const DEFAULT_ERROR_CODE = 1400;
    
    /**
     * Code d'erreur pour une transaction déjà commencée
     */
    public const TRANSACTION_ALREADY_STARTED = 1401;
    
    /**
     * Code d'erreur pour une absence de transaction active
     */
    public const NO_ACTIVE_TRANSACTION = 1402;
    
    /**
     * Code d'erreur pour une erreur de validation de transaction
     */
    public const COMMIT_FAILED = 1403;
    
    /**
     * Code d'erreur pour une erreur d'annulation de transaction
     */
    public const ROLLBACK_FAILED = 1404;
    
    /**
     * Code d'erreur pour une transaction imbriquée non supportée
     */
    public const NESTED_TRANSACTION_NOT_SUPPORTED = 1405;
    
    /**
     * Crée une exception pour une transaction déjà commencée
     *
     * @return self
     */
    public static function alreadyStarted(): self
    {
        return new self(
            "Une transaction est déjà en cours",
            self::TRANSACTION_ALREADY_STARTED
        );
    }
    
    /**
     * Crée une exception pour une absence de transaction active
     *
     * @param string $operation Opération tentée (commit ou rollback)
     * @return self
     */
    public static function noActiveTransaction(string $operation): self
    {
        return new self(
            "Impossible d'effectuer l'opération '$operation' : aucune transaction active",
            self::NO_ACTIVE_TRANSACTION,
            null,
            ['operation' => $operation]
        );
    }
    
    /**
     * Crée une exception pour un échec de validation de transaction
     *
     * @param string $reason Raison de l'échec
     * @return self
     */
    public static function commitFailed(string $reason): self
    {
        return new self(
            "La validation de la transaction a échoué : $reason",
            self::COMMIT_FAILED,
            null,
            ['reason' => $reason]
        );
    }
    
    /**
     * Crée une exception pour un échec d'annulation de transaction
     *
     * @param string $reason Raison de l'échec
     * @return self
     */
    public static function rollbackFailed(string $reason): self
    {
        return new self(
            "L'annulation de la transaction a échoué : $reason",
            self::ROLLBACK_FAILED,
            null,
            ['reason' => $reason]
        );
    }
} 