<?php
declare(strict_types=1);

namespace Cocoon\Database;

use PDO;
use Throwable;
use Cocoon\Database\Exception\ConnectionException;
use Cocoon\Database\Query\Builder;

/**
 * Gestionnaire ORM et de connexions à la base de données
 *
 * Cette classe fournit un gestionnaire centralisé pour:
 *  - Gérer plusieurs connexions PDO simultanées
 *  - Accéder à une connexion par défaut ou spécifique
 *  - Exécuter des transactions de manière sécurisée
 *  - Journaliser les requêtes SQL pour le débogage
 *  - Gérer l'ORM et les modèles
 *
 * Pattern: Singleton avec registre pour les connexions multiples
 */
class Orm
{
    /**
     * Stocke les connexions PDO actives
     *
     * @var array<string, PDO>
     */
    protected static array $connections = [];
    
    /**
     * Connexion par défaut
     *
     * @var string
     */
    protected static string $defaultConnection = 'default';
    
    /**
     * Historique des requêtes exécutées (pour débogage)
     *
     * @var array<string>
     */
    protected static array $queryLog = [];
    
    /**
     * Indique si l'enregistrement des requêtes est activé
     *
     * @var bool
     */
    protected static bool $logEnabled = false;

    /**
     * Configuration de la base de données
     *
     * @var array<string, mixed>
     */
    protected static array $config = [
        'db.driver' => null,
        'db.connection' => null,
        'db.cache.path' => null,
        'pagination.renderer' => null
    ];

    /**
     * Initialise le gestionnaire ORM avec une connexion par défaut
     *
     * @param string $engine Le moteur de base de données
     * @param array $db_config Configuration de la base de données
     * @return void
     */
    public static function manager(string $engine, array $db_config): void
    {
        $db = DatabaseConnection::make($engine, $db_config);
        // Ajoute la connexion par défaut
        self::addConnection('default', $db);
        
        // Stocke la configuration
        self::$config = [
            'db.driver' => $engine,
            'db.connection' => self::connection('default'),
            'db.cache.path' => $db_config['db_cache_path'] ?? null,
            'pagination.renderer' => $db_config['pagination_renderer'] ?? null
        ];
    }

    /**
     * Récupère un paramètre de configuration
     *
     * @param string $key La clé du paramètre
     * @return mixed La valeur du paramètre ou null si non défini
     */
    public static function getConfig(string $key): mixed
    {
        return self::$config[$key] ?? null;
    }

    /**
     * Définit un paramètre de configuration
     *
     * @param string $key La clé du paramètre
     * @param mixed $value La valeur du paramètre
     * @return void
     */
    public static function setConfig(string $key, mixed $value): void
    {
        self::$config[$key] = $value;
    }
    /**
     * Récupère une instance du Query Builder
     *
     * @param string $table
     * @return Query\Builder
     */
    public static function table(string $table): mixed
    {
        return DB::table($table);
    }
    /**
     * Exécute une requête SQL
     *
     * @param string $sql
     * @param array $bindParams
     * @return mixed
     */
    public static function query(string $sql, array $bindParams = []): mixed
    {
        return DB::query($sql, $bindParams);
    }

    /**
     * Récupère tous les paramètres de configuration
     *
     * @return array<string, mixed> Tous les paramètres de configuration
     */
    public static function getAllConfig(): array
    {
        return self::$config;
    }

    /**
     * Ajoute une nouvelle connexion à la liste des connexions disponibles
     *
     * @param string $name Nom de la connexion
     * @param PDO $connection Instance PDO de la connexion
     * @return void
     */
    public static function addConnection(string $name, PDO $connection): void
    {
        self::$connections[$name] = $connection;
    }

    /**
     * Récupère une connexion par son nom
     *
     * @param string|null $name Nom de la connexion (null pour utiliser la connexion par défaut)
     * @return PDO Instance PDO de la connexion
     * @throws ConnectionException Si la connexion n'existe pas
     */
    public static function connection(?string $name = null): PDO
    {
        $name = $name ?? self::$defaultConnection;
        
        if (!isset(self::$connections[$name])) {
            throw new ConnectionException("La connexion '$name' n'existe pas");
        }
        
        return self::$connections[$name];
    }

    /**
     * Définit la connexion par défaut
     *
     * @param string $name Nom de la connexion
     * @return void
     * @throws ConnectionException Si la connexion n'existe pas
     */
    public static function setDefaultConnection(string $name): void
    {
        if (!isset(self::$connections[$name])) {
            throw new ConnectionException("La connexion '$name' n'existe pas");
        }
        
        self::$defaultConnection = $name;
    }

    /**
     * Active l'enregistrement des requêtes SQL
     *
     * @return void
     */
    public static function enableQueryLog(): void
    {
        self::$logEnabled = true;
    }

    /**
     * Désactive l'enregistrement des requêtes SQL
     *
     * @return void
     */
    public static function disableQueryLog(): void
    {
        self::$logEnabled = false;
    }

    /**
     * Ajoute une requête à l'historique
     *
     * @param string $query Requête SQL
     * @return void
     */
    public static function addQuery(string $query): void
    {
        if (self::$logEnabled) {
            self::$queryLog[] = $query;
        }
    }

    /**
     * Récupère toutes les requêtes SQL exécutées par le Builder
     *
     * @return array<string> Liste des requêtes SQL
     */
    public static function getBuilderQueries(): array
    {
        return Builder::$SQLS;
    }

    /**
     * Récupère toutes les requêtes SQL (Builder + autres)
     *
     * @return array<string> Liste complète des requêtes SQL
     */
    public static function getAllQueries(): array
    {
        return array_merge(self::$queryLog, Builder::$SQLS);
    }

    /**
     * Récupère l'historique des requêtes
     *
     * @return array<string> Liste des requêtes exécutées
     */
    public static function getQueryLog(): array
    {
        return self::$queryLog;
    }

    /**
     * Vide l'historique des requêtes
     *
     * @return void
     */
    public static function flushQueryLog(): void
    {
        self::$queryLog = [];
    }

    /**
     * Démarre une transaction sur la connexion spécifiée
     *
     * @param string|null $connection Nom de la connexion (null pour utiliser la connexion par défaut)
     * @return bool Résultat de l'opération
     * @throws ConnectionException Si la connexion n'existe pas
     */
    public static function beginTransaction(?string $connection = null): bool
    {
        return self::connection($connection)->beginTransaction();
    }

    /**
     * Valide une transaction sur la connexion spécifiée
     *
     * @param string|null $connection Nom de la connexion (null pour utiliser la connexion par défaut)
     * @return bool Résultat de l'opération
     * @throws ConnectionException Si la connexion n'existe pas
     */
    public static function commit(?string $connection = null): bool
    {
        return self::connection($connection)->commit();
    }

    /**
     * Annule une transaction sur la connexion spécifiée
     *
     * @param string|null $connection Nom de la connexion (null pour utiliser la connexion par défaut)
     * @return bool Résultat de l'opération
     * @throws ConnectionException Si la connexion n'existe pas
     */
    public static function rollback(?string $connection = null): bool
    {
        return self::connection($connection)->rollBack();
    }

    /**
     * Vérifie si une transaction est active sur la connexion spécifiée
     *
     * @param string|null $connection Nom de la connexion (null pour utiliser la connexion par défaut)
     * @return bool True si une transaction est active
     * @throws ConnectionException Si la connexion n'existe pas
     */
    public static function inTransaction(?string $connection = null): bool
    {
        return self::connection($connection)->inTransaction();
    }

    /**
     * Exécute une fonction dans une transaction et gère automatiquement le commit ou le rollback
     *
     * @param callable $callback Fonction à exécuter dans la transaction
     * @param string|null $connection Nom de la connexion (null pour utiliser la connexion par défaut)
     * @return mixed Résultat de la fonction exécutée
     * @throws Throwable Si une erreur survient pendant l'exécution de la fonction
     * @throws ConnectionException Si la connexion n'existe pas
     */
    public static function transaction(callable $callback, ?string $connection = null): mixed
    {
        $pdo = self::connection($connection);
        
        if ($pdo->inTransaction()) {
            return $callback();
        }
        
        try {
            $pdo->beginTransaction();
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
