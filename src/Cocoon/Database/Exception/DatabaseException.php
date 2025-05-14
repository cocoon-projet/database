<?php
declare(strict_types=1);

namespace Cocoon\Database\Exception;

use Exception;

/**
 * Exception de base pour toutes les erreurs liées à la base de données
 *
 * Cette classe sert de classe parente pour toutes les exceptions spécifiques
 * liées aux opérations de base de données.
 */
class DatabaseException extends Exception
{
    /**
     * Code d'erreur par défaut
     */
    protected const DEFAULT_ERROR_CODE = 1000;

    /**
     * Contexte supplémentaire associé à l'exception
     *
     * @var array
     */
    protected array $context = [];

    /**
     * Constructeur
     *
     * @param string $message Message d'erreur
     * @param int|null $code Code d'erreur (par défaut: DEFAULT_ERROR_CODE)
     * @param \Throwable|null $previous Exception précédente
     * @param array $context Contexte supplémentaire
     */
    public function __construct(
        string $message,
        ?int $code = null,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code ?? static::DEFAULT_ERROR_CODE, $previous);
        $this->context = $context;
    }

    /**
     * Récupère le contexte de l'exception
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
    
    /**
     * Ajoute des informations de contexte à l'exception
     *
     * @param string $key Clé du contexte
     * @param mixed $value Valeur du contexte
     * @return self
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }
} 