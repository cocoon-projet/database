<?php
use Cocoon\Database\Orm;
use Cocoon\Dependency\DI;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    protected $pdo;

    protected function setUp(): void
    {
        $config = [
            'db_connection' => 'sqlite',
            'db' => [
                'mysql' => [
                    'db_name' => 'cocoon',
                    'db_user' => 'root',
                    'db_password' => 'root',
                    'db_host' => 'localhost',
                    'mode' => 'development',
                    'db_cache_path' => dirname(__DIR__) . '/database/cache/'
                ],
                'sqlite' => [
                    'path' => __DIR__ . '/database/database.sqlite',
                    'mode' => 'development',
                    'db_cache_path' => __DIR__ . '/database/cache/'
                ]
            ]
        ];
        Orm::manager($config['db_connection'], $config['db']['sqlite']);
    }

    public function testConnection()
    {
        $this->assertIsObject(DI::get('db.connection'));
        $this->assertInstanceOf(PDO::class, DI::get('db.connection'));
    }



}

