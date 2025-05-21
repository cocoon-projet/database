<?php
declare(strict_types=1);

namespace Cocoon\Database\Migrations\Schema;

class Blueprint
{
    protected $tableName;
    protected $columns = [];
    protected $commands = [];
    protected $isAlter = false;
    protected $platform = '';
    
    public function __construct($tableName, $isAlter = false)
    {
        $this->tableName = $tableName;
        $this->isAlter = $isAlter;
    }
    
    public function integer($name)
    {
        $column = Column::integer($name);
        $this->columns[] = $column;
        return $column;
    }
    
    public function string($name, $length = 255)
    {
        $column = Column::string($name, $length);
        $this->columns[] = $column;
        return $column;
    }
    
    public function text($name)
    {
        $column = Column::text($name);
        $this->columns[] = $column;
        return $column;
    }
    
    public function boolean($name)
    {
        $column = Column::boolean($name);
        $this->columns[] = $column;
        return $column;
    }
    
    public function datetime($name)
    {
        $column = Column::datetime($name);
        $this->columns[] = $column;
        return $column;
    }
    
    public function date($name)
    {
        $column = Column::date($name);
        $this->columns[] = $column;
        return $column;
    }
    
    public function decimal($name, $precision = 8, $scale = 2)
    {
        $column = Column::decimal($name, $precision, $scale);
        $this->columns[] = $column;
        return $column;
    }
    
    public function timestamps()
    {
        $this->datetime('created_at')->nullable();
        $this->datetime('updated_at')->nullable();
    }
    
    public function id()
    {
        return $this->integer('id')->autoIncrement();
    }
    
    public function addColumn($column)
    {
        $this->commands[] = ['add', $column];
    }
    
    public function dropColumn($name)
    {
        $this->commands[] = ['drop', $name];
    }
    
    public function modifyColumn($column)
    {
        $this->commands[] = ['modify', $column];
    }
    
    public function renameColumn($from, $to)
    {
        $this->commands[] = ['rename', $from, $to];
    }
    
    /**
     * Ajoute une colonne entière qui servira de clé étrangère
     *
     * @param string $column Nom de la colonne
     * @return Column
     */
    public function foreignId($column)
    {
        $col = $this->integer($column)->unsigned();
        return $col;
    }
    
    public function toSql($platform)
    {
        $columns = [];
        $this->platform = $platform;
        $foreignKeys = [];

        foreach ($this->columns as $column) {
            $columns[] = $column->getDefinition($this->platform);
            
            // Collecter les définitions de clés étrangères
            if ($column->isForeignKey()) {
                $fkDefinition = $column->getForeignKeyDefinition();
                if ($fkDefinition) {
                    $foreignKeys[] = $fkDefinition;
                }
            }
        }
        
        $sql = "CREATE TABLE `{$this->tableName}` (\n    ";
        $sql .= implode(",\n    ", $columns);
        
        // Ajouter les contraintes de clés étrangères
        if (!empty($foreignKeys)) {
            $sql .= ",\n    ";
            $sql .= implode(",\n    ", $foreignKeys);
        }
        
        $sql .= "\n)";
        
        if ($this->platform == 'mysql') {
            $sql .= " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }
        
        return $sql;
    }
    
    public function toAlterSql($platform)
    {
        $queries = [];
        $this->platform = $platform;
        foreach ($this->commands as $command) {
            switch ($command[0]) {
                case 'add':
                    $column = $command[1];
                    $queries[] = "ALTER TABLE `{$this->tableName}` ADD COLUMN "
                    . $column->getDefinition($this->platform);
                    break;
                    
                case 'drop':
                    $name = $command[1];
                    $queries[] = "ALTER TABLE `{$this->tableName}` DROP COLUMN `{$name}`";
                    break;
                    
                case 'modify':
                    $column = $command[1];
                    if ($this->platform == 'mysql') {
                        $queries[] = "ALTER TABLE `{$this->tableName}` MODIFY COLUMN "
                        . $column->getDefinition($this->platform);
                    } elseif ($this->platform == 'sqlite') {
                        // SQLite doesn't support direct column modification
                        // Would need to implement table recreation
                        throw new \Exception("SQLite does not support direct column modification");
                    }
                    break;
                    
                case 'rename':
                    $from = $command[1];
                    $to = $command[2];
                    if ($this->platform == 'mysql') {
                        $queries[] = "ALTER TABLE `{$this->tableName}` RENAME COLUMN `{$from}` TO `{$to}`";
                    } elseif ($this->platform == 'sqlite') {
                        $queries[] = "ALTER TABLE `{$this->tableName}` RENAME COLUMN `{$from}` TO `{$to}`";
                    }
                    break;
            }
        }
        //dumpe($queries);
        return $queries;
    }
    
    public function getIndexSql($platform)
    {
        $queries = [];
        $this->platform = $platform;
        foreach ($this->columns as $column) {
            if ($column->isIndex() && !$column->isPrimary() && !$column->isUnique()) {
                $name = $column->getName();
                $indexName = "idx_{$this->tableName}_{$name}";
                $queries[] = "CREATE INDEX `{$indexName}` ON `{$this->tableName}` (`{$name}`)";
            }
            
            if ($column->isUnique() && !$column->isPrimary()) {
                $name = $column->getName();
                $indexName = "unq_{$this->tableName}_{$name}";
                $queries[] = "CREATE UNIQUE INDEX `{$indexName}` ON `{$this->tableName}` (`{$name}`)";
            }
        }
        //dumpe($queries);
        return $queries;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }
}
