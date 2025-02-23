<?php
declare(strict_types=1);

namespace Cocoon\Database\Engine;

use PDO;
use Exception;

/**
 * Class Mysql
 * @package Cocoon\Database\Engine
 */
class Mysql extends PDO
{
    /**
     * Initialise une connection mysql
     *
     * Mysql constructor.
     */
    public function __construct($config)
    {
        try {
            parent::__construct(
                'mysql:host=' . $config['db_host'] .
                ';dbname=' . $config['db_name'] . '',
                $config['db_user'],
                $config['db password']
            );
            $this->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8');
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            if ($config['mode'] == 'development') {
                $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } else {
                $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            }
        } catch (Exception $e) {
            echo "Connection a MySQL impossible : ", $e->getMessage();
            die();
        }
    }

    /**
     * Sql limit pour mysql,
     *
     * @param int $count
     * @param int $offset
     * @return string
     */
    public function limit($count, $offset)
    {
        $limit = '';
        if ($count > 0) {
            $limit = ' LIMIT ' . $count;
            if ($offset > 0) {
                $limit .= ' OFFSET ' . $offset;
            }
        }
        return $limit;
    }
}
