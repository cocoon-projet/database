<?php
declare(strict_types=1);

namespace Cocoon\Database;

use Cocoon\Dependency\DI;

class Orm
{
    /**
     * @param string $engine
     * @param array $db_config
     * @return void
     */
    public static function manager(string $engine, array $db_config) :void
    {
        $db = DatabaseConnection::make($engine, $db_config);
        DI::addServices([
            'db.driver' => $engine,
            'db.connection' => $db,
            'db.cache.path' => $db_config['db_cache_path'],
            'pagination.renderer' => $db_config['pagination_renderer']
        ]);
    }
}
