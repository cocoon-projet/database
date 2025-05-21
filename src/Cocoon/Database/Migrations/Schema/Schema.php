<?php
declare(strict_types=1);

namespace Cocoon\Database\Migrations\Schema;

use Cocoon\Database\Orm;
use Cocoon\Database\Exception\MigrationException;

class Schema
{
    protected $pdo;
    protected $platform;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->platform = Orm::getConfig('db.driver');
    }
    
    public function create($tableName, $callback)
    {
        $blueprint = new Blueprint($tableName);
        $callback($blueprint);
        
        $this->executeCreateTable($blueprint);
        $this->executeIndexes($blueprint);
        
        return $blueprint;
    }
    
    public function table($tableName, $callback)
    {
        $blueprint = new Blueprint($tableName, true);
        $callback($blueprint);
        
        $this->executeAlterTable($blueprint);
        $this->executeIndexes($blueprint);
        
        return $blueprint;
    }
    
    public function drop($tableName)
    {
        $sql = "DROP TABLE IF EXISTS `{$tableName}`";
        return $this->pdo->exec($sql);
    }
    
    protected function executeCreateTable(Blueprint $blueprint)
    {
        try {
            $sql = $blueprint->toSql($this->platform);
            return $this->pdo->exec($sql);
        } catch (\Exception $e) {
            throw MigrationException::schemaError(
                $blueprint->getTableName(),
                "Erreur lors de la création de la table : " . $e->getMessage()
            );
        }
    }
    
    protected function executeAlterTable(Blueprint $blueprint)
    {
        $queries = $blueprint->toAlterSql($this->platform);
        
        $this->pdo->beginTransaction();
        
        try {
            foreach ($queries as $sql) {
                $this->pdo->exec($sql);
            }
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            
            // Vérifier si c'est une opération non supportée
            if (strpos($e->getMessage(), "does not support") !== false) {
                throw MigrationException::unsupportedOperation(
                    "Modification de colonne",
                    $this->platform
                );
            }
            
            throw MigrationException::schemaError(
                $blueprint->getTableName(),
                "Erreur lors de la modification de la table : " . $e->getMessage()
            );
        }
    }
    
    protected function executeIndexes(Blueprint $blueprint)
    {
        $queries = $blueprint->getIndexSql($this->platform);
        
        if (empty($queries)) {
            return true;
        }
        
        $this->pdo->beginTransaction();
        
        try {
            foreach ($queries as $sql) {
                $this->pdo->exec($sql);
            }
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw MigrationException::schemaError(
                $blueprint->getTableName(),
                "Erreur lors de la création des index : " . $e->getMessage()
            );
        }
    }
}
