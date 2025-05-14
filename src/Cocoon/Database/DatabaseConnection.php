<?php
declare(strict_types=1);

namespace Cocoon\Database;

use Cocoon\Database\Engine\Mysql;
use Cocoon\Database\Engine\Sqlite;
use InvalidArgumentException;

/**
 * Classe DatabaseConnection
 * Gère la connexion aux bases de données (MySQL ou SQLite).
 * Factory pour créer les instances de connexion appropriées.
 *
 * @package Cocoon\Database
 */
class DatabaseConnection
{
    /**
     * Liste des moteurs de base de données supportés.
     */
    private const SUPPORTED_ENGINES = ['mysql', 'sqlite'];

    /**
     * Se connecte à la base de données définie en configuration.
     * Crée une instance du moteur de base de données approprié.
     *
     * @param string $engine Nom du moteur de base de données ('mysql' ou 'sqlite')
     * @param array $db_config Configuration de la connexion
     * @return Mysql|Sqlite Instance du moteur de base de données
     * @throws InvalidArgumentException Si le moteur n'est pas supporté
     */
    public static function make(string $engine, array $db_config): Mysql|Sqlite
    {
        if (!in_array($engine, self::SUPPORTED_ENGINES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Moteur de base de données non supporté : %s. Les moteurs supportés sont : %s',
                    $engine,
                    implode(', ', self::SUPPORTED_ENGINES)
                )
            );
        }

        $databaseClass = 'Cocoon\\Database\\Engine\\' . ucfirst($engine);
        
        return new $databaseClass($db_config);
    }
}
