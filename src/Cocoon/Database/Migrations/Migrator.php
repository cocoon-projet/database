<?php
namespace Cocoon\Database\Migrations;

use PDO;
use Exception;
use Cocoon\Database\Orm;
use Cocoon\Database\Migrations\Built;
use Cocoon\Database\Exception\MigrationException;

class Migrator
{
    protected $db;
    protected $migrationsPath;

    protected $table = 'migrations';

    public function __construct($migrationsPath)
    {
        Built::connection();
        $this->db = Orm::getConfig('db.connection');
        
        // Vérifier que le chemin des migrations est valide
        if (!is_dir($migrationsPath)) {
            throw MigrationException::invalidPath($migrationsPath);
        }
        
        $this->migrationsPath = $migrationsPath;
        $this->createMigrationsTable();
    }

    protected function createMigrationsTable()
    {
        // Utiliser le système de Schema pour créer la table des migrations
        // afin d'être compatible avec tous les SGBD
        $schema = new \Cocoon\Database\Migrations\Schema\Schema($this->db);
        
        // Création de la table migrations si elle n'existe pas
        if (!$this->tableExists($this->table)) {
            $schema->create($this->table, function ($table) {
                $table->id();
                $table->string('migration', 255);
                $table->integer('batch');
                $table->datetime('created_at')->default('CURRENT_TIMESTAMP');
            });
        }
        
        // Création de la table migration_logs si elle n'existe pas
        if (!$this->tableExists('migration_logs')) {
            $schema->create('migration_logs', function ($table) {
                $table->id();
                $table->string('migration', 255);
                $table->string('status', 255);
                $table->text('message')->nullable();
                $table->datetime('created_at')->default('CURRENT_TIMESTAMP');
            });
        }
    }
    
    /**
     * Vérifie si une table existe dans la base de données
     *
     * @param string $tableName Nom de la table
     * @return bool
     */
    protected function tableExists($tableName)
    {
        $driver = Orm::getConfig('db.driver');
        
        if ($driver === 'mysql') {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
        } else { // SQLite
            $stmt = $this->db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = ?");
            $stmt->execute([$tableName]);
        }
        
        return $stmt->fetch() !== false;
    }

    public function run()
    {
        $migrations = $this->getPendingMigrations();
        $batch = $this->getNextBatchNumber();

        foreach ($migrations as $migration) {
            $migrationFile = $this->migrationsPath . '/' . $migration . '.php';
            
            try {
                // Vérifier si le fichier existe
                if (!file_exists($migrationFile)) {
                    throw MigrationException::missingFile($migrationFile);
                }
                
                require_once $migrationFile;
                $className = $this->getClassName($migration);
                
                // Vérifier si la classe existe
                if (!class_exists($className)) {
                    throw MigrationException::missingClass($className, $migrationFile);
                }
                
                $migrationInstance = new $className($this->db);
                $migrationInstance->up();
                $migrationInstance->recordMigration($migration, $batch);
                $this->logMigration($migration, 'applied');
                echo "Migration {$migration} applied.\n";
            } catch (MigrationException $e) {
                $this->logMigration($migration, 'failed', $e->getMessage());
                echo "Migration {$migration} failed: " . $e->getMessage() . "\n";
                throw $e; // Remonter l'exception spécifique
            } catch (Exception $e) {
                $this->logMigration($migration, 'failed', $e->getMessage());
                echo "Migration {$migration} failed: " . $e->getMessage() . "\n";
                throw MigrationException::executionFailed($migration, 'up', $e->getMessage());
            }
        }
    }

    public function rollback()
    {
        $lastBatch = $this->getLastBatchNumber();
        $migrations = $this->getMigrationsForBatch($lastBatch);

        foreach ($migrations as $migration) {
            $migrationFile = $this->migrationsPath . '/' . $migration . '.php';
            
            try {
                // Vérifier si le fichier existe
                if (!file_exists($migrationFile)) {
                    throw MigrationException::missingFile($migrationFile);
                }
                
                require_once $migrationFile;
                $className = $this->getClassName($migration);
                
                // Vérifier si la classe existe
                if (!class_exists($className)) {
                    throw MigrationException::missingClass($className, $migrationFile);
                }
                
                $migrationInstance = new $className($this->db);
                $migrationInstance->down();
                $migrationInstance->deleteMigration($migration);
                $this->logMigration($migration, 'rolled back');
                echo "Migration {$migration} rolled back.\n";
            } catch (MigrationException $e) {
                $this->logMigration($migration, 'rollback failed', $e->getMessage());
                echo "Rollback of migration {$migration} failed: " . $e->getMessage() . "\n";
                throw $e; // Remonter l'exception spécifique
            } catch (Exception $e) {
                $this->logMigration($migration, 'rollback failed', $e->getMessage());
                echo "Rollback of migration {$migration} failed: " . $e->getMessage() . "\n";
                throw MigrationException::executionFailed($migration, 'down', $e->getMessage());
            }
        }
    }

    /**
     * Annule toutes les migrations appliquées
     * 
     * @return void
     * @throws MigrationException En cas d'erreur lors du rollback
     */
    public function reset()
    {
        $batches = $this->getAllBatchNumbers();
        
        // Parcourir les batches dans l'ordre décroissant pour annuler les migrations les plus récentes d'abord
        rsort($batches);
        
        foreach ($batches as $batch) {
            $migrations = $this->getMigrationsForBatch($batch);
            
            // Parcourir les migrations de chaque batch dans l'ordre inverse
            $migrations = array_reverse($migrations);
            
            foreach ($migrations as $migration) {
                $migrationFile = $this->migrationsPath . '/' . $migration . '.php';
                
                try {
                    // Vérifier si le fichier existe
                    if (!file_exists($migrationFile)) {
                        throw MigrationException::missingFile($migrationFile);
                    }
                    
                    require_once $migrationFile;
                    $className = $this->getClassName($migration);
                    
                    // Vérifier si la classe existe
                    if (!class_exists($className)) {
                        throw MigrationException::missingClass($className, $migrationFile);
                    }
                    
                    $migrationInstance = new $className($this->db);
                    $migrationInstance->down();
                    $migrationInstance->deleteMigration($migration);
                    $this->logMigration($migration, 'reset');
                    echo "Migration {$migration} reset.\n";
                } catch (MigrationException $e) {
                    $this->logMigration($migration, 'reset failed', $e->getMessage());
                    echo "Reset of migration {$migration} failed: " . $e->getMessage() . "\n";
                    throw $e; // Remonter l'exception spécifique
                } catch (Exception $e) {
                    $this->logMigration($migration, 'reset failed', $e->getMessage());
                    echo "Reset of migration {$migration} failed: " . $e->getMessage() . "\n";
                    throw MigrationException::executionFailed($migration, 'down', $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Supprime toutes les tables et réapplique toutes les migrations
     * 
     * @return void
     * @throws MigrationException En cas d'erreur lors de l'opération
     */
    public function fresh()
    {
        try {
            // Supprimer toutes les tables
            $this->dropAllTables();
            
            // Recréer la table des migrations
            $this->createMigrationsTable();
            
            // Réappliquer toutes les migrations
            $this->run();
            
            echo "Base de données rafraîchie avec succès.\n";
        } catch (Exception $e) {
            throw MigrationException::resetFreshFailed('fresh', $e->getMessage());
        }
    }
    
    /**
     * Supprime toutes les tables de la base de données
     * 
     * @return void
     * @throws Exception En cas d'erreur lors de la suppression
     */
    protected function dropAllTables()
    {
        $driver = Orm::getConfig('db.driver');
        $schema = new \Cocoon\Database\Migrations\Schema\Schema($this->db);
        
        // Désactiver les contraintes de clés étrangères temporairement
        if ($driver === 'mysql') {
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
        } else if ($driver === 'sqlite') {
            $this->db->exec('PRAGMA foreign_keys = OFF');
        }
        
        try {
            // Obtenir la liste des tables
            $tables = $this->getAllTables();
            
            // Supprimer chaque table
            foreach ($tables as $table) {
                $schema->drop($table);
                echo "Table {$table} supprimée.\n";
            }
        } finally {
            // Réactiver les contraintes de clés étrangères
            if ($driver === 'mysql') {
                $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
            } else if ($driver === 'sqlite') {
                $this->db->exec('PRAGMA foreign_keys = ON');
            }
        }
    }
    
    /**
     * Récupère la liste de toutes les tables de la base de données
     * 
     * @return array Liste des noms de tables
     */
    protected function getAllTables(): array
    {
        $driver = Orm::getConfig('db.driver');
        $tables = [];
        
        if ($driver === 'mysql') {
            $stmt = $this->db->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
        } else { // SQLite
            $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tables[] = $row['name'];
            }
        }
        
        return $tables;
    }
    
    /**
     * Récupère tous les numéros de batch existants
     * 
     * @return array Liste des numéros de batch
     */
    protected function getAllBatchNumbers(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT batch FROM {$this->table} ORDER BY batch");
        $batches = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $batches[] = (int)$row['batch'];
        }
        
        return $batches;
    }

    protected function getPendingMigrations()
    {
        $appliedMigrations = $this->getAppliedMigrations();
        $allMigrations = array_map(function ($file) {
            return pathinfo($file, PATHINFO_FILENAME);
        }, glob($this->migrationsPath . '/*.php'));

        return array_diff($allMigrations, $appliedMigrations);
    }

    protected function getAppliedMigrations()
    {
        $stmt = $this->db->query("SELECT migration FROM migrations");
        $appliedMigrations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $appliedMigrations[] = $row['migration'];
        }
        return $appliedMigrations;
    }

    protected function getMigrationsForBatch($batch)
    {
        $stmt = $this->db->prepare("SELECT migration FROM migrations WHERE batch = ?");
        $stmt->execute([$batch]);
        $migrations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $migrations[] = $row['migration'];
        }
        return $migrations;
    }

    protected function getNextBatchNumber()
    {
        $stmt = $this->db->query("SELECT MAX(batch) FROM migrations");
        $lastBatch = $stmt->fetchColumn();
        return $lastBatch === false ? 1 : $lastBatch + 1;
    }

    protected function getLastBatchNumber()
    {
        $stmt = $this->db->query("SELECT MAX(batch) FROM migrations");
        $lastBatch = $stmt->fetchColumn();
        return $lastBatch === false ? 0 : $lastBatch;
    }

    protected function getClassName($migration)
    {
        // Extraire la partie du nom sans timestamp 
        $nameParts = explode('_', $migration);
        // On considère que les 4 premiers éléments constituent le timestamp (YYYY_MM_DD_HHMMSS)
        if (count($nameParts) > 4 && preg_match('/^\d{4}$/', $nameParts[0])) {
            // Extraire la partie du nom sans timestamp (après les 4 premiers segments)
            $nameWithoutTimestamp = implode('_', array_slice($nameParts, 4));
            
            // Convertir le nom en PascalCase pour la classe
            return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $nameWithoutTimestamp)));
        } else {
            // Si le format ne correspond pas à un timestamp, utiliser le nom entier
            return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $migration)));
        }
    }

    protected function logMigration($migration, $status, $message = null)
    {
        $stmt = $this->db->prepare("INSERT INTO migration_logs (migration, status, message, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute([$migration, $status, $message]);
    }

    /**
     * Affiche l'état des migrations
     *
     * @return array Liste des migrations avec leur statut
     */
    public function status(): array
    {
        $allMigrations = array_map(function ($file) {
            return pathinfo($file, PATHINFO_FILENAME);
        }, glob($this->migrationsPath . '/*.php'));
        
        $appliedMigrations = $this->getAppliedMigrations();
        
        $status = [];
        
        // Traiter toutes les migrations
        foreach ($allMigrations as $migration) {
            $status[$migration] = [
                'file' => $migration . '.php',
                'applied' => in_array($migration, $appliedMigrations),
                'batch' => $this->getBatchForMigration($migration)
            ];
        }
        
        // Trier par nom de fichier
        ksort($status);
        
        return $status;
    }
    
    /**
     * Récupère le numéro de batch pour une migration
     *
     * @param string $migration Nom de la migration
     * @return int|null Numéro de batch ou null si non appliquée
     */
    protected function getBatchForMigration(string $migration): ?int
    {
        $stmt = $this->db->prepare("SELECT batch FROM {$this->table} WHERE migration = ?");
        $stmt->execute([$migration]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (int)$result['batch'] : null;
    }
}