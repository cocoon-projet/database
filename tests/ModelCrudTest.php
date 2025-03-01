<?php
declare(strict_types=1);

use Cocoon\Database\DB;
use Cocoon\Database\Orm;
use Cocoon\Dependency\DI;
use PHPUnit\Framework\TestCase;

class User extends \Cocoon\Database\Model {
    protected static $table = 'users';
}
class ModelCrudTest extends TestCase
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

    public function testSave()
    {
        $user = new User();
        $user->username = 'john_doe';
        $user->email = 'john.doe@example.com';
        $user->save();

        $result = DB::query('SELECT * FROM users WHERE username = ?', ['john_doe']);
        $this->assertNotEmpty($result);
        $this->assertEquals('john_doe', $result->username);
        $this->assertEquals('john.doe@example.com', $result->email);
    }

    public function testDelete()
    {
        $user = new User();
        $user->username = 'john_doe';
        $user->email = 'john.doe@example.com';
        $user->save();
        $user = new User($user->getId());
        $user->delete();
        $result = $this->pdo->query('SELECT * FROM users WHERE username = "john_doe"')->fetch();
        $this->assertEmpty($result);
    }

    public function testFind()
    {
        $user = new User();
        $user->username = 'john_doe';
        $user->email = 'john.doe@example.com';
        $user->save();
        $foundUser = User::find($user->getId());
        $this->assertNotNull($foundUser);
        $this->assertEquals('john_doe', $foundUser->username);
        $this->assertEquals('john.doe@example.com', $foundUser->email);
    }

    public function testFindAll()
    {
        $user1 = new User();
        $user1->username = 'john_doe';
        $user1->email = 'john.doe@example.com';
        $user1->save();

        $user2 = new User();
        $user2->username = 'jane_doe';
        $user2->email = 'jane.doe@example.com';
        $user2->save();

        $users = $user1::findAll();
        $this->assertCount(2, $users);
    }

    public function testTransaction()
    {
        $user = new User();

        User::transaction(function() use ($user) {
            $user->username = 'john_doe';
            $user->email = 'john.doe@example.com';
            $user->save();
        });

        $result = DB::query('select * from users where username = ?' , ['john_doe']);
        $this->assertNotEmpty($result);
    }
}