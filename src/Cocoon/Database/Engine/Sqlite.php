<?php
declare(strict_types=1);

namespace Cocoon\Database\Engine;

use PDO;
use Exception;

/**
 * Classe Sqlite
 * Gère la connexion et les opérations spécifiques à SQLite.
 * Étend PDO pour fournir une interface de base de données SQLite.
 *
 * @package Cocoon\Database\Engine
 */
class Sqlite extends PDO
{
    /**
     * Initialise une connexion à une base SQLite.
     * Configure les attributs PDO en fonction du mode d'environnement.
     *
     * @param array{
     *     path: string,
     *     mode: 'development'|'testing'|'production'
     * } $config Configuration de la connexion
     * @throws Exception Si la connexion échoue
     */
    public function __construct(array $config)
    {
        try {
            parent::__construct(
                'sqlite:' . $config['base_path'] . $config['path'],
                'charset=UTF-8'
            );

            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

            $this->setErrorMode($config['mode']);
        } catch (Exception $e) {
            throw new Exception("Connexion à SQLite impossible : " . $e->getMessage());
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
     * Génère la clause LIMIT pour SQLite.
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
