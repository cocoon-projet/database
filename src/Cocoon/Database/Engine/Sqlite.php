<?php
declare(strict_types=1);

namespace Cocoon\Database\Engine;

use PDO;
use Exception;

/**
 * Class Sqlite
 * @package Cocoon\Database\Engine
 */
class Sqlite extends PDO
{
    /**
     * Initialise une connection à une base sqlite
     *
     * Sqlite constructor.
     */
    public function __construct($config)
    {
        try {
            parent::__construct(
                'sqlite:' . $config['path'],
                'charset=UTF-8'
            );
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            if ($config['mode'] == 'development') {
                $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } else {
                $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            }
        } catch (Exception $e) {
            echo "Connection a sqlite impossible : ", $e->getMessage();
            die();
        }
    }

    /**
     * Sql limit pour sqlite
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
