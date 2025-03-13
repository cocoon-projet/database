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

    public function testNot()
    {
        $users = DB::table('users')
            ->not('username', 'user1')
            ->get();

        $this->assertCount(2, $users);
        $this->assertNotEquals('user1', $users[0]->username);
    }

    public function testAndNotIn()
    {
        $users = DB::table('users')
            ->where('username', 'user1')
            ->andNotIn('id', [2, 3])
            ->get();

        $this->assertCount(1, $users);
        $this->assertEquals('user1', $users[0]->username);
        $this->assertEquals(1, $users[0]->id); // Ajout d'une vérification de l'ID
    }

    public function testOr()
    {
        $users = DB::table('users')
            ->where('username', 'user1')
            ->or('username', 'user2')
            ->get();

        $this->assertCount(2, $users);
        $this->assertEquals('user1', $users[0]->username);
        $this->assertEquals('user2', $users[1]->username);
    }

    public function testAndNot()
    {
        $users = DB::table('users')
            ->where('username', 'user1')
            ->andNot('id', 2)
            ->get();

        $this->assertCount(1, $users);
        $this->assertEquals('user1', $users[0]->username);
    }

    public function testIn()
    {
        $users = DB::table('users')
            ->in('id', [1, 2])
            ->get();

        $this->assertCount(2, $users);
        $this->assertEquals('user1', $users[0]->username);
        $this->assertEquals('user2', $users[1]->username);
    }

    public function testNotIn()
    {
        $users = DB::table('users')
            ->notIn('id', [1, 2])
            ->get();

        $this->assertCount(1, $users);
        $this->assertEquals('user3', $users[0]->username);
    }

    public function testGroupBy()
    {
        // Ajout de données avec des doublons
        $this->pdo->exec("INSERT INTO users (username, email) VALUES 
            ('user1', 'user1.bis@test.com'),
            ('user2', 'user2.bis@test.com')");

        $users = DB::table('users')
            ->select('username, COUNT(*) as total')
            ->groupBy('username')
            ->orderBy('username', 'asc')
            ->get();

        $this->assertCount(3, $users); // 3 usernames uniques

        // Vérification des totaux pour chaque username
        $this->assertEquals(2, $users[0]->total); // user1 a 2 entrées
        $this->assertEquals(2, $users[1]->total); // user2 a 2 entrées
        $this->assertEquals(1, $users[2]->total); // user3 a 1 entrée
    }

    public function testHaving()
    {
        // Ajout de données avec des doublons
        $this->pdo->exec("INSERT INTO users (username, email) VALUES 
            ('user1', 'user1.bis@test.com'),
            ('user2', 'user2.bis@test.com')");

        $users = DB::table('users')
            ->select('username, COUNT(*) as total')
            ->groupBy('username')
            ->having('COUNT(*) > 1')
            ->orderBy('username', 'asc')
            ->get();

        $this->assertCount(2, $users); // Seulement user1 et user2 ont plus d'une entrée
        $this->assertEquals('user1', $users[0]->username);
        $this->assertEquals(2, $users[0]->total);
        $this->assertEquals('user2', $users[1]->username);
        $this->assertEquals(2, $users[1]->total);
    }/*
    public function testOrHaving()
    {
        // Ajout de données avec des doublons 
        $this->pdo->exec("INSERT INTO users (username, email) VALUES 
            ('user1', 'user1.bis@test.com'),
            ('user2', 'user2.bis@test.com'),
            ('user2', 'user2.ter@test.com')");

        $users = DB::table('users')
            ->select('username, COUNT(*) as total')
            ->groupBy('username')
            ->having('COUNT(*) = 1')
            ->orHaving('COUNT(*) = 3')
            ->orderBy('username', 'asc')
            ->get();

        $this->assertCount(2, $users); // user3 has count=1, user2 has count=3

        $userTotals = [];
        foreach ($users as $user) {
            $userTotals[$user->username] = $user->total;
        }

        // Verify individual counts
        $this->assertEquals(1, $userTotals['user3']); // user3 has 1 record
        $this->assertEquals(3, $userTotals['user2']); // user2 has 3 records

        // Make sure user1 is not included (has count=2)
        $this->assertArrayNotHasKey('user1', $userTotals);
    }*/
    public function testlists()
    {
        $usernames = DB::table('users')
            ->lists('username');

        $this->assertIsArray($usernames);
        $this->assertCount(3, $usernames);
        $this->assertEquals('user1', $usernames[1]);
        $this->assertEquals('user2', $usernames[2]);
        $this->assertEquals('user3', $usernames[3]);
    }
    public function testlistsWithKeys()
    {
        $usernames = DB::table('users')
            ->lists('username', 'id');

        $this->assertIsArray($usernames);
        $this->assertArrayHasKey(1, $usernames);
        $this->assertEquals('user1', $usernames[1]);
        $this->assertEquals('user2', $usernames[2]);
        $this->assertEquals('user3', $usernames[3]);
    }
}