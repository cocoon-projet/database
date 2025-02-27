<?php
declare(strict_types=1);

namespace Cocoon\Database;

use Cocoon\Database\Query\Builder;
use Cocoon\Dependency\DI;

/**
 * Class DB
 * @package Cocoon\Database
 */
class DB
{

    /**
     * Gestion native des requêtes sql
     * ex: DB::query('select * from articles);
     *
     * @param $sql
     * @param array $bindParams
     * @return mixed
     */
    public static function query($sql, $bindParams = [])
    {
        $stmt = (DI::get('db.connection'))->prepare($sql);
        if (count($bindParams) > 0) {
            $stmt->execute($bindParams);
        } else {
            $stmt->execute();
        }
        $select = substr(trim($sql), 0, 6);
        if (strtolower($select) == 'select') {
            $result = $stmt->fetchAll();
            return count($result) == 1 ? $result[0] : $result;
        }
    }

    /**
     * Gestion des requêtes à partir de la class Query\Builder
     * ex: DB:table('articles')->select('titre,slug')->orderBy('id')->limit(10);
     *
     * @param $table
     * @return Builder
     */
    public static function table($table)
    {
        return Builder::init()->from($table);
    }
}
