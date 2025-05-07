<?php
declare(strict_types=1);

namespace Cocoon\Database;

/**
 * Gère la connection aux bases de donnée (mysql ou sqlite)
 *
 * Class DatabaseConnection
 * @package Cocoon\Database
 */
class DatabaseConnection
{
    /**
     * Se connecte à la base de donnée definit en configuration
     *
     * @return <object data="" type=""></object> \Cocoon\Engine\Mysql ou Sqlite
     */
    public static function make($engine, $db_config)
    {
        $database = 'Cocoon\\Database\\Engine\\' . ucfirst($engine);
        return new $database($db_config);
    }
}
