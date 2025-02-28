<?php
declare(strict_types=1);

use Cocoon\Database\Orm;
use Cocoon\Dependency\DI;
use PHPUnit\Framework\TestCase;
use Cocoon\Database\Query\Builder;


class BuilderCrudTest extends TestCase
{
    protected $pdo;

    protected function setUp(): void
    {
        $config = [
            'db_connection' => 'sqlite',
            'db' => [
                'sqlite' => [
                    'path' => ':memory:',
                    'mode' => 'testing',
                    'db_cache_path' => ''
                ]
            ]
        ];
        Orm::manager($config['db_connection'], $config['db']['sqlite']);
        $this->pdo = DI::get('db.connection');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL
        )');
    }

    public function testInsert()
    {
        $builder = new Builder();
        $builder->from('users')->insert(['username' => 'john_doe', 'email' => 'john.doe@example.com']);
        $result = (new Builder())->from('users')->get();
        $this->assertCount(1, $result);
        $this->assertEquals('john_doe', $result[0]->username);
    }

    public function testDelete()
    {
        $builder = new Builder();
        $builder->from('users')->where('id', 1)->delete();
        $result = (new Builder())->from('users')->where('id', 1)->get();
        $this->assertEmpty($result);
    }

    public function testUpdate()
    { 
        $builder = new Builder();
        $builder->from('users')->insert(['username' => 'john_doe', 'email' => 'john.doe@example.com']);
        $builder = new Builder();
        $builder->from('users')->where('id', 1)->update(['username' => 'jane_smith', 'email' => 'jane_smith@example.com']);
        $result = (new Builder())->from('users')->where('id', 1)->get();

        $this->assertEquals('jane_smith', $result[0]->username);
    }
}