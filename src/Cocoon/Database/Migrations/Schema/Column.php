<?php
declare(strict_types=1);

namespace Cocoon\Database\Migrations\Schema;

class Column
{
    protected $name;
    protected $type;
    protected $length;
    protected $nullable = false;
    protected $default = null;
    protected $unique = false;
    protected $primary = false;
    protected $autoIncrement = false;
    protected $unsigned = false;
    protected $index = false;
    
    // Propriétés pour les clés étrangères
    protected $foreignKey = false;
    protected $references = null;
    protected $on = null;
    protected $onDelete = null;
    protected $onUpdate = null;

    public function __construct($name, $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public static function integer($name)
    {
        return new self($name, 'INTEGER');
    }

    public static function string($name, $length = 255)
    {
        $column = new self($name, 'VARCHAR');
        $column->length = $length;
        return $column;
    }

    public static function text($name)
    {
        return new self($name, 'TEXT');
    }

    public static function boolean($name)
    {
        return new self($name, 'BOOLEAN');
    }

    public static function datetime($name)
    {
        return new self($name, 'DATETIME');
    }

    public static function date($name)
    {
        return new self($name, 'DATE');
    }

    public static function decimal($name, $precision = 8, $scale = 2)
    {
        $column = new self($name, 'DECIMAL');
        $column->length = $precision . ',' . $scale;
        return $column;
    }

    public function primary()
    {
        $this->primary = true;
        $this->nullable = false;
        return $this;
    }

    public function autoIncrement()
    {
        $this->autoIncrement = true;
        $this->primary();
        return $this;
    }

    public function nullable()
    {
        $this->nullable = true;
        return $this;
    }

    public function default($value)
    {
        $this->default = $value;
        return $this;
    }

    public function unique()
    {
        $this->unique = true;
        return $this;
    }

    public function unsigned()
    {
        $this->unsigned = true;
        return $this;
    }

    public function index()
    {
        $this->index = true;
        return $this;
    }

    public function getDefinition($platform)
    {
        $definition = $this->name . ' ' . $this->type;
        if ($this->length) {
            $definition .= '(' . $this->length . ')';
        }

        if ($this->primary) {
            $definition .= ' PRIMARY KEY';
        }

        if ($this->autoIncrement) {
            if ($platform == 'mysql') {
                $definition .= ' AUTO_INCREMENT';
            } elseif ($platform == 'sqlite') {
                $definition .= ' AUTOINCREMENT';
            }
        }
        
        if ($this->unsigned && in_array($this->type, ['INTEGER', 'DECIMAL'])) {
            $definition .= ' UNSIGNED';
        }
        
        if (!$this->nullable) {
            $definition .= ' NOT NULL';
        } else {
            $definition .= ' NULL';
        }
        
        if ($this->default !== null) {
            if (is_string($this->default) && !is_numeric($this->default)) {
                $definition .= " DEFAULT '" . $this->default . "'";
            } else {
                $definition .= ' DEFAULT ' . $this->default;
            }
        }
        
        if ($this->unique) {
            $definition .= ' UNIQUE';
        }
        
        return $definition;
    }

    public function getName()
    {
        return $this->name;
    }

    public function isIndex()
    {
        return $this->index;
    }

    public function isPrimary()
    {
        return $this->primary;
    }

    public function isUnique()
    {
        return $this->unique;
    }

    /**
     * Définit la colonne comme clé étrangère
     *
     * @return $this
     */
    public function foreign()
    {
        $this->foreignKey = true;
        return $this;
    }

    /**
     * Spécifie la colonne référencée par cette clé étrangère
     *
     * @param string $column Nom de la colonne référencée
     * @return $this
     */
    public function references($column)
    {
        $this->foreignKey = true;
        $this->references = $column;
        return $this;
    }

    /**
     * Spécifie la table référencée par cette clé étrangère
     *
     * @param string $table Nom de la table référencée
     * @return $this
     */
    public function on($table)
    {
        $this->on = $table;
        return $this;
    }

    /**
     * Spécifie l'action à effectuer lors de la suppression
     * de la ligne référencée
     *
     * @param string $action Action (CASCADE, SET NULL, RESTRICT, NO ACTION)
     * @return $this
     */
    public function onDelete($action)
    {
        $this->onDelete = $action;
        return $this;
    }

    /**
     * Spécifie l'action à effectuer lors de la mise à jour
     * de la ligne référencée
     *
     * @param string $action Action (CASCADE, SET NULL, RESTRICT, NO ACTION)
     * @return $this
     */
    public function onUpdate($action)
    {
        $this->onUpdate = $action;
        return $this;
    }
    
    /**
     * Vérifie si la colonne est une clé étrangère
     *
     * @return bool
     */
    public function isForeignKey()
    {
        return $this->foreignKey;
    }
    
    /**
     * Récupère la définition de la clé étrangère
     *
     * @return string|null
     */
    public function getForeignKeyDefinition()
    {
        if (!$this->foreignKey || !$this->references || !$this->on) {
            return null;
        }
        
        $definition = "FOREIGN KEY (`{$this->name}`) REFERENCES `{$this->on}`(`{$this->references}`)";
        
        if ($this->onDelete) {
            $definition .= " ON DELETE {$this->onDelete}";
        }
        
        if ($this->onUpdate) {
            $definition .= " ON UPDATE {$this->onUpdate}";
        }
        
        return $definition;
    }
}
