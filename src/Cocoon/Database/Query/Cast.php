<?php
declare(strict_types=1);

namespace Cocoon\Database\Query;

use Cocoon\Dependency\DI;

class Cast
{
    public const SQLITE = 'sqlite';
    public const MYSQL = 'mysql';
    
    /**
     * Le type de base de données actuel
     */
    protected static string $databaseType = '';

    /**
     * Définit le type de base de données
     */
    public static function setDatabaseType(): void
    {
        self::$databaseType = strtolower(DI::get('db.driver'));
    }

    /**
     * Cast une expression en entier
     */
    public static function asInteger(string $expression): string
    {
        return match (self::$databaseType) {
            self::SQLITE => "CAST({$expression} AS INTEGER)",
            self::MYSQL => "CAST({$expression} AS SIGNED INTEGER)",
            default => $expression
        };
    }

    /**
     * Cast une expression en décimal
     */
    public static function asDecimal(string $expression, int $precision = 10, int $scale = 2): string
    {
        return match (self::$databaseType) {
            self::SQLITE => "CAST({$expression} AS DECIMAL({$precision},{$scale}))",
            self::MYSQL => "CAST({$expression} AS DECIMAL({$precision},{$scale}))",
            default => $expression
        };
    }

    /**
     * Cast une expression en chaîne
     */
    public static function asString(string $expression): string
    {
        return match (self::$databaseType) {
            self::SQLITE => "CAST({$expression} AS TEXT)",
            self::MYSQL => "CAST({$expression} AS CHAR)",
            default => $expression
        };
    }
}
