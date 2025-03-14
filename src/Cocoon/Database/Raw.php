<?php
declare(strict_types=1);

namespace Cocoon\Database;

class Raw
{
    /**
     * L'expression SQL brute
     *
     * @var string
     */
    protected $value;

    /**
     * Crée une nouvelle instance Raw
     *
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Obtient la valeur de l'expression
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Convertit l'objet en chaîne
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getValue();
    }
}
