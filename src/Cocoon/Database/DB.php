<?php
declare(strict_types=1);

namespace Cocoon\Database;

use Cocoon\Database\Orm;
use Cocoon\Database\Query\Raw;
use Cocoon\Database\Query\Builder;

/**
 * Class DB
 * @package Cocoon\Database
 */
class DB
{

    /**
     * Exécute une requête SQL native avec gestion des paramètres liés
     *
     * @param string $sql La requête SQL à exécuter
     * @param array $bindParams Les paramètres à lier à la requête
     * @return array|object|null Le résultat de la requête
     * @throws \PDOException Si une erreur survient lors de l'exécution de la requête
     */
    public static function query(string $sql, array $bindParams = []): array|object|null
    {
        try {
            $connection = Orm::getConfig('db.connection');
            if (!$connection instanceof \PDO) {
                throw new \RuntimeException('La connexion à la base de données n\'est pas valide');
            }

            $stmt = $connection->prepare($sql);
            $stmt->execute($bindParams);

            // Vérifie si c'est une requête SELECT
            if (preg_match('/^\s*SELECT\b/i', $sql)) {
                $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                return count($result) === 1 ? $result[0] : $result;
            }

            return null;
        } catch (\PDOException $e) {
            throw new \PDOException(
                "Erreur lors de l'exécution de la requête : " . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
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

    /**
     * Crée une expression SQL brute
     *
     * @param string $value L'expression SQL
     * @return \Cocoon\Database\Query\Raw
     */
    public static function raw($value)
    {
        return new Raw($value);
    }
}
