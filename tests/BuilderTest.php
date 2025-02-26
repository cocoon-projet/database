<?php
declare(strict_types=1);

use Cocoon\Database\Orm;
use Cocoon\Dependency\DI;
use PHPUnit\Framework\TestCase;
use Cocoon\Database\Query\Builder;


class BuilderTest extends TestCase
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

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            content TEXT NOT NULL,
            article_id INTEGER,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (article_id) REFERENCES articles(id)
        )');
    }

    public function testInsert()
    {
        $builder = Builder::init();
        $builder->from('users')->insert(['username' => 'john_doe', 'email' => 'john.doe@example.com']);
        $result = Builder::init()->from('users')->get();
        $this->assertCount(1, $result);
        $this->assertEquals('john_doe', $result[0]['username']);
    }
    public function testUpdate()
    {
        $builder = Builder::init();
        $builder->from('users')->update(['username' => 'jane_smith', 'email' => 'jane_smith@example.com'])->where('id', 1);
        $result = Builder::init()->from('users')->where('id', 1)->get();
        $this->assertEquals('jane_smith', $result[0]['username']);
    }

    public function testDelete()
    {
        $builder = Builder::init();
        $builder->from('users')->delete()->where('id', 1);
        $result = Builder::init()->from('users')->where('id', 1)->get();
        $this->assertEmpty($result);
    }
}