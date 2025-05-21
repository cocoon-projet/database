<?php
namespace Cocoon\Database\Migrations;

use Cocoon\Database\Orm;
use Cocoon\Database\Migrations\Schema\Schema;

class Built {
    protected static $instances = [];
    
    public static function connection() {
        return self::addConnection(Orm::getConfig('db.driver'));
    }
    
    private static function addConnection($name = 'default') {
        $pdo = Orm::getConfig('db.connection');
        return self::$instances[$name] = $pdo;
    }
    
    public static function schema() {
        return new Schema(self::connection());
    }
}