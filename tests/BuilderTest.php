<?php
use Cocoon\Database\DB;
use Cocoon\Database\Orm;
use Cocoon\Dependency\DI;
use PHPUnit\Framework\TestCase;

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
        
        // Création des tables
        $this->pdo->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT,
            email TEXT
        )');

        // Insertion de données de test
        $this->pdo->exec("INSERT INTO users (username, email) VALUES 
            ('user1', 'user1@test.com'),
            ('user2', 'user2@test.com'),
            ('user3', 'user3@test.com')");
    }

    public function testSelect()
    {
        $users = DB::table('users')->select('username')->get();
        $this->assertCount(3, $users);
        $this->assertObjectHasProperty('username', $users[0]);
    }

    public function testWhere() 
    {
        $users = DB::table('users')->where('username', 'user1')->get();
        $this->assertCount(1, $users);
        $this->assertEquals('user1', $users[0]->username);
    }

    public function testAndWhere()
    {
        $users = DB::table('users')
            ->where('username', 'user1')
            ->and('email', 'user1@test.com')
            ->get();
            
        $this->assertCount(1, $users);
        $this->assertEquals('user1@test.com', $users[0]->email);
    }

    public function testOrWhere()
    {
        $users = DB::table('users')
            ->where('username', 'user1')
            ->or('username', 'user2')
            ->get();
            
        $this->assertCount(2, $users);
    }

    public function testOrderBy()
    {
        $users = DB::table('users')
            ->orderBy('username', 'desc')
            ->get();
            
        $this->assertEquals('user3', $users[0]->username);
    }

    public function testLimit()
    {
        $users = DB::table('users')
            ->limit(2)
            ->get();
            
        $this->assertCount(2, $users);
    }

    public function testBetween()
    {
        $users = DB::table('users')
            ->between('id', 1, 2)
            ->get();
            
        $this->assertCount(2, $users);
    }

    public function testNotBetween() 
    {
        $users = DB::table('users')
            ->notBetween('id', 1, 2)
            ->get();
            
        $this->assertCount(1, $users);
    }

    public function testFirst()
    {
        $user = DB::table('users')->first();
        $this->assertEquals('user1', $user->username);
    }

    public function testLast() 
    {
        $user = DB::table('users')->last();
        $this->assertEquals('user3', $user->username);
    }
}