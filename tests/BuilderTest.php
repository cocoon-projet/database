<?php
use Cocoon\Database\DB;
use Cocoon\Database\Orm;
use Cocoon\Dependency\DI;
use Cocoon\Database\Query\Cast;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    protected $pdo;

    protected function setUp(): void
    {
        $config = [
            'db_driver' => 'sqlite',
            'db' => [
                'sqlite' => [
                    'base_path' => '',
                    'path' => ':memory:',
                    'mode' => 'testing',
                    'pagination_renderer' => 'bootstrap5',
                    'db_cache_path' => ''
                ]
            ]
        ];
        Orm::manager($config['db_driver'], $config['db'][$config['db_driver']]);
        $this->pdo = Orm::getConfig('db.connection');

        // Création des tables
        $this->pdo->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT,
            email TEXT,
            points INTEGER DEFAULT 0
        )');

        // Insertion de données de test
        $this->pdo->exec("INSERT INTO users (username, email, points) VALUES 
            ('user1', 'user1@test.com', 10),
            ('user2', 'user2@test.com', 20),
            ('user3', 'user3@test.com', 30)");
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
    }
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

    public function testIncrement()
    {
        // Incrémente les points de user1 de 5
        DB::table('users')
            ->where('username', 'user1')
            ->increment('points', 5);

        $user = DB::table('users')
            ->where('username', 'user1')
            ->first();

        $this->assertNotNull($user);
        $this->assertEquals(15, $user->points); // 10 + 5 = 15
    }

    public function testDecrement()
    {
        // Décrémente les points de user2 de 8
        DB::table('users')
            ->where('username', 'user2')
            ->decrement('points', 8);

        $user = DB::table('users')
            ->where('username', 'user2')
            ->first();

        $this->assertEquals(12, $user->points); // 20 - 8 = 12
    }
    
    public function testOrHaving()
    {
        // Nettoyons d'abord la table
        $this->pdo->exec("DELETE FROM users");
        
        // Insérons des données de test spécifiques avec des points par défaut
        $this->pdo->exec("INSERT INTO users (username, email, points) VALUES 
            ('user1', 'user1@test.com', 10),
            ('user1', 'user1.bis@test.com', 10),
            ('user2', 'user2@test.com', 20),
            ('user2', 'user2.bis@test.com', 20),
            ('user2', 'user2.ter@test.com', 20),
            ('user3', 'user3@test.com', 30)");

        // Ajoutons d'abord un debug pour voir les résultats intermédiaires
        $debug = DB::table('users')
            ->select('username, COUNT(*) as total')
            ->groupBy('username')
            ->get();

        // Vérifions les totaux avant d'appliquer les conditions HAVING
        foreach ($debug as $d) {
            echo sprintf("Debug - Username: %s, Total: %d\n", $d->username, $d->total);
        }

        $users = DB::table('users')
            ->select([
                'username', 
                DB::raw(Cast::asInteger('COUNT(*)') . ' as total')
            ])
            ->groupBy('username')
            ->having('COUNT(*) = 1')
            ->orHaving('COUNT(*) = 3')
            ->orderBy('username', 'asc')
            ->get();

        // Stockons les totaux dans un tableau pour faciliter la vérification
        $userTotals = [];
        foreach ($users as $user) {
            $userTotals[$user->username] = $user->total;
        }

        // Vérifions que nous avons exactement 2 résultats
        $this->assertCount(2, $users, "Nous devrions avoir exactement 2 résultats (user2 avec 3 enregistrements et user3 avec 1 enregistrement)");
        
        // Vérifions les totaux spécifiques
        $this->assertEquals(1, $userTotals['user3'], "user3 devrait avoir 1 enregistrement");
        $this->assertEquals(3, $userTotals['user2'], "user2 devrait avoir 3 enregistrements");
        $this->assertArrayNotHasKey('user1', $userTotals, "user1 ne devrait pas être dans les résultats");
    }
}