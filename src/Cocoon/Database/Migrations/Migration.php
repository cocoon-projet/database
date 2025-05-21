<?php
declare(strict_types=1);

namespace Cocoon\Database\Migrations;

use Cocoon\Dependency\DI;

abstract class Migration
{
    protected $db;
    protected $table = 'migrations';

    protected $tableName = '';
    public function __construct($db)
    {
        $this->db = $db;
    }

    abstract public function up();
    abstract public function down();

    public function addTable($tableName, $columns)
    {
        $columnsSql = implode(", ", $columns);
        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} ({$columnsSql})";
        $this->db->exec($sql);
    }

    public function recordMigration($migration, $batch)
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (migration, batch) VALUES (?, ?)");
        $stmt->execute([$migration, $batch]);
    }

    public function deleteMigration($migration)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE migration = ?");
        $stmt->execute([$migration]);
    }
}
