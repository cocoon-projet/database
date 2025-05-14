<?php
declare(strict_types=1);

namespace Cocoon\Database\Engine;

use PDO;
use Exception;

/**
 * Classe Mysql
 * Gère la connexion et les opérations spécifiques à MySQL.
 * Étend PDO pour fournir une interface de base de données MySQL.
 *
 * @package Cocoon\Database\Engine
 */
class Mysql extends PDO
{
    /**
     * Initialise une connexion à une base MySQL.
     * Configure les attributs PDO en fonction du mode d'environnement.
     *
     * @param array{
     *     db_host: string,
     *     db_name: string,
     *     db_user: string,
     *     db_password: string,
     *     mode: 'development'|'testing'|'production'
     * } $config Configuration de la connexion
     * @throws Exception Si la connexion échoue
     */
    public function __construct(array $config)
    {
        try {
            parent::__construct(
                'mysql:host=' . $config['db_host'] .
                ';dbname=' . $config['db_name'],
                $config['db_user'],
                $config['db_password']
            );

            $this->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8mb4');
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

            $this->setErrorMode($config['mode']);
        } catch (Exception $e) {
            throw new Exception("Connexion à MySQL impossible : " . $e->getMessage());
        }
    }

    /**
     * Configure le mode de gestion des erreurs en fonction de l'environnement.
     *
     * @param string $mode Mode d'environnement ('development', 'testing' ou 'production')
     */
    private function setErrorMode(string $mode): void
    {
        if ($mode === 'development' || $mode === 'testing') {
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } else {
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        }
    }

    /**
     * Génère la clause LIMIT pour MySQL.
     *
     * @param int $count Nombre maximum d'enregistrements à retourner
     * @param int $offset Position de départ pour la pagination
     * @return string Clause LIMIT formatée
     */
    public function limit(int $count, int $offset): string
    {
        if ($count <= 0) {
            return '';
        }

        $limit = ' LIMIT ' . $count;
        
        if ($offset > 0) {
            $limit .= ' OFFSET ' . $offset;
        }

        return $limit;
    }
}
