<?php
namespace Tests;

use Tests\Models\User;
use Cocoon\Database\Orm;
use Cocoon\Dependency\DI;
use Tests\Models\Article;
use Tests\Models\Comment;
use PHPUnit\Framework\TestCase;

class ModelFinderTest extends TestCase
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
                    'pagination_renderer' => 'bootstrap5',
                    'db_cache_path' => ''
                ]
            ]
        ];
        Orm::manager($config['db_connection'], $config['db']['sqlite']);
        $this->pdo = Orm::getConfig('db.connection');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS phones (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            phone_number VARCHAR(255) NOT NULL,
            user_id INTEGER,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            user_id INTEGER,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            content TEXT NOT NULL,
            user_id INTEGER,
            article_id INTEGER,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (article_id) REFERENCES articles(id)
        )');
        
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS article_tag (
            article_id INTEGER,
            tag_id INTEGER,
            PRIMARY KEY (article_id, tag_id),
            FOREIGN KEY (article_id) REFERENCES articles(id),
            FOREIGN KEY (tag_id) REFERENCES tags(id)
        )');

        $tags = [
            ['name' => 'tag1'],
            ['name' => 'tag2'],
            ['name' => 'tag3'],
            ['name' => 'tag4'],
            ['name' => 'tag5'],
            ['name' => 'tag6'],
            ['name' => 'tag7'],
            ['name' => 'tag8'],
            ['name' => 'tag9'],
            ['name' => 'tag10'],
            ['name' => 'tag11'],
            ['name' => 'tag12'],
            ['name' => 'tag13'],
            ['name' => 'tag14'],
            ['name' => 'tag15'],
            ['name' => 'tag16'],
            ['name' => 'tag17'],
            ['name' => 'tag18'],
            ['name' => 'tag19'],
            ['name' => 'tag20'],
            ['name' => 'tag21'],
            ['name' => 'tag22'],
            ['name' => 'tag23'],
            ['name' => 'tag24'],
            ['name' => 'tag25'],
            ['name' => 'tag26'],
            ['name' => 'tag27'],
            ['name' => 'tag28'],
            ['name' => 'tag29'],
            ['name' => 'tag30'],
            ['name' => 'tag31'],
            ['name' => 'tag32'],
            ['name' => 'tag33'],
            ['name' => 'tag34'],
            ['name' => 'tag35'],
            ['name' => 'tag36'],
            ['name' => 'tag37'],
            ['name' => 'tag38'],
            ['name' => 'tag39'],
            ['name' => 'tag40'],
            ['name' => 'tag41'],
            ['name' => 'tag42'],
            ['name' => 'tag43'],
            ['name' => 'tag44'],
            ['name' => 'tag45'],
            ['name' => 'tag46'],
            ['name' => 'tag47'],
            ['name' => 'tag48'],
            ['name' => 'tag49'],
            ['name' => 'tag50'],
            ['name' => 'tag51'],
            ['name' => 'tag52'],
            ['name' => 'tag53'],
            ['name' => 'tag54'],
            ['name' => 'tag55'],
            ['name' => 'tag56'],
            ['name' => 'tag57'],
            ['name' => 'tag58'],
            ['name' => 'tag59'],
            ['name' => 'tag60'],
            ['name' => 'tag61'],
            ['name' => 'tag62'],
            ['name' => 'tag63'],
            ['name' => 'tag64'],
            ['name' => 'tag65'],
            ['name' => 'tag66'],
            ['name' => 'tag67'],
            ['name' => 'tag68'],
            ['name' => 'tag69'],
            ['name' => 'tag70'],
            ['name' => 'tag71'],
            ['name' => 'tag72'],
            ['name' => 'tag73'],
            ['name' => 'tag74'],
            ['name' => 'tag75'],
            ['name' => 'tag76'],
            ['name' => 'tag77'],
            ['name' => 'tag78'],
            ['name' => 'tag79'],
            ['name' => 'tag80'],
            ['name' => 'tag81'],
            ['name' => 'tag82'],
            ['name' => 'tag83'],
            ['name' => 'tag84'],
            ['name' => 'tag85'],
            ['name' => 'tag86'],
            ['name' => 'tag87'],
            ['name' => 'tag88'],
            ['name' => 'tag89'],
            ['name' => 'tag90'],
            ['name' => 'tag91'],
            ['name' => 'tag92'],
            ['name' => 'tag93'],
            ['name' => 'tag94'],
            ['name' => 'tag95'],
            ['name' => 'tag96'],
            ['name' => 'tag97'],
            ['name' => 'tag98'],
            ['name' => 'tag99'],
            ['name' => 'tag100'],
            // (100 tags)
        ];

        $users = [
            ['username' => 'john_doe', 'email' => 'john.doe@example.com'],
            ['username' => 'jane_smith', 'email' => 'jane.smith@example.com'],
            ['username' => 'peter_jones', 'email' => 'peter.jones@example.com'],
            // (3 auteurs)
        ];

        $phones = [
            ['phone_number' => '1234567890', 'user_id' => 1],
            ['phone_number' => '0987654321', 'user_id' => 2],
            ['phone_number' => '1230984567', 'user_id' => 3],
            // (3 phones)
        ];

        $articles = [
            ['title' => 'Article 1', 'content' => 'Contenu de l\'article 1', 'user_id' => 1],
            ['title' => 'Article 2', 'content' => 'Contenu de l\'article 2', 'user_id' => 2],
            ['title' => 'Article 3', 'content' => 'Contenu de l\'article 3', 'user_id' => 1],
            ['title' => 'Article 4', 'content' => 'Contenu de l\'article 4', 'user_id' => 3],
            ['title' => 'Article 5', 'content' => 'Contenu de l\'article 5', 'user_id' => 2],
            ['title' => 'Article 6', 'content' => 'Contenu de l\'article 6', 'user_id' => 3],
            ['title' => 'Article 7', 'content' => 'Contenu de l\'article 7', 'user_id' => 1],
            ['title' => 'Article 8', 'content' => 'Contenu de l\'article 8', 'user_id' => 2],
            ['title' => 'Article 9', 'content' => 'Contenu de l\'article 9', 'user_id' => 3],
            ['title' => 'Article 10', 'content' => 'Contenu de l\'article 10', 'user_id' => 1],
            ['title' => 'Article 11', 'content' => 'Contenu de l\'article 11', 'user_id' => 2],
            ['title' => 'Article 12', 'content' => 'Contenu de l\'article 12', 'user_id' => 3],
            ['title' => 'Article 13', 'content' => 'Contenu de l\'article 13', 'user_id' => 1],
            ['title' => 'Article 14', 'content' => 'Contenu de l\'article 14', 'user_id' => 2],
            ['title' => 'Article 15', 'content' => 'Contenu de l\'article 15', 'user_id' => 3],
            ['title' => 'Article 16', 'content' => 'Contenu de l\'article 16', 'user_id' => 1],
            ['title' => 'Article 17', 'content' => 'Contenu de l\'article 17', 'user_id' => 2],
            ['title' => 'Article 18', 'content' => 'Contenu de l\'article 18', 'user_id' => 3],
            ['title' => 'Article 19', 'content' => 'Contenu de l\'article 19', 'user_id' => 1],
            ['title' => 'Article 20', 'content' => 'Contenu de l\'article 20', 'user_id' => 2],
            ['title' => 'Article 21', 'content' => 'Contenu de l\'article 21', 'user_id' => 3],
            ['title' => 'Article 22', 'content' => 'Contenu de l\'article 22', 'user_id' => 1],
            ['title' => 'Article 23', 'content' => 'Contenu de l\'article 23', 'user_id' => 2],
            ['title' => 'Article 24', 'content' => 'Contenu de l\'article 24', 'user_id' => 3],
            ['title' => 'Article 25', 'content' => 'Contenu de l\'article 25', 'user_id' => 1],
            // ... (25 articles) 9 user 1, 8 user 2, 8 user 3
        ];

        $comments = [
            ['content' => 'Commentaire 1', 'user_id' => 2, 'article_id' => 1],
            ['content' => 'Commentaire 2', 'user_id' => 1, 'article_id' => 2],
            ['content' => 'Commentaire 3', 'user_id' => 3, 'article_id' => 1],
            ['content' => 'Commentaire 4', 'user_id' => 1, 'article_id' => 3],
            ['content' => 'Commentaire 5', 'user_id' => 2, 'article_id' => 2],
            ['content' => 'Commentaire 6', 'user_id' => 3, 'article_id' => 2],
            ['content' => 'Commentaire 7', 'user_id' => 1, 'article_id' => 3],
            ['content' => 'Commentaire 8', 'user_id' => 2, 'article_id' => 4],
            ['content' => 'Commentaire 9', 'user_id' => 3, 'article_id' => 4],
            ['content' => 'Commentaire 10', 'user_id' => 1, 'article_id' => 5],
            ['content' => 'Commentaire 11', 'user_id' => 2, 'article_id' => 5],
            ['content' => 'Commentaire 12', 'user_id' => 3, 'article_id' => 5],
            ['content' => 'Commentaire 13', 'user_id' => 1, 'article_id' => 6],
            ['content' => 'Commentaire 14', 'user_id' => 2, 'article_id' => 6],
            ['content' => 'Commentaire 15', 'user_id' => 3, 'article_id' => 6],
            ['content' => 'Commentaire 16', 'user_id' => 1, 'article_id' => 7],
            ['content' => 'Commentaire 17', 'user_id' => 2, 'article_id' => 7],
            ['content' => 'Commentaire 18', 'user_id' => 3, 'article_id' => 7],
            ['content' => 'Commentaire 19', 'user_id' => 1, 'article_id' => 8],
            ['content' => 'Commentaire 20', 'user_id' => 2, 'article_id' => 8],
            ['content' => 'Commentaire 21', 'user_id' => 3, 'article_id' => 8],
            ['content' => 'Commentaire 22', 'user_id' => 1, 'article_id' => 9],
            ['content' => 'Commentaire 23', 'user_id' => 2, 'article_id' => 9],
            ['content' => 'Commentaire 24', 'user_id' => 3, 'article_id' => 9],
            ['content' => 'Commentaire 25', 'user_id' => 1, 'article_id' => 10],
            // ... (25 commentaires) 10 article 1, 5 article 2, 5 article 3, 5 article 4,
            //  5 article 5, 5 article 6, 5 article 7, 5 article 8, 5 article 9, 5 article 10
        ];

        $article_tags = [
            ['article_id' => 1, 'tag_id' => 1],
            ['article_id' => 1, 'tag_id' => 2],
            ['article_id' => 1, 'tag_id' => 3],
            ['article_id' => 1, 'tag_id' => 4],
            ['article_id' => 1, 'tag_id' => 5],
            ['article_id' => 1, 'tag_id' => 6],
            ['article_id' => 1, 'tag_id' => 7],
            ['article_id' => 1, 'tag_id' => 8],
            ['article_id' => 1, 'tag_id' => 9],
            ['article_id' => 1, 'tag_id' => 10],
            ['article_id' => 1, 'tag_id' => 11],
            ['article_id' => 1, 'tag_id' => 12],
            ['article_id' => 1, 'tag_id' => 13],
            ['article_id' => 1, 'tag_id' => 14],
            ['article_id' => 1, 'tag_id' => 15],
            ['article_id' => 1, 'tag_id' => 16],
            ['article_id' => 1, 'tag_id' => 17],
            ['article_id' => 1, 'tag_id' => 18],
            ['article_id' => 1, 'tag_id' => 19],
            ['article_id' => 1, 'tag_id' => 20],
            ['article_id' => 1, 'tag_id' => 21],
            ['article_id' => 1, 'tag_id' => 22],
            ['article_id' => 1, 'tag_id' => 23],
            ['article_id' => 1, 'tag_id' => 24],
            ['article_id' => 1, 'tag_id' => 25],
            ['article_id' => 1, 'tag_id' => 26],
            ['article_id' => 1, 'tag_id' => 27],
            ['article_id' => 1, 'tag_id' => 28],
            ['article_id' => 1, 'tag_id' => 29],
            ['article_id' => 1, 'tag_id' => 30],
            ['article_id' => 1, 'tag_id' => 31],
            ['article_id' => 1, 'tag_id' => 32],
            ['article_id' => 1, 'tag_id' => 33],
            ['article_id' => 1, 'tag_id' => 34],
            ['article_id' => 1, 'tag_id' => 35],
            ['article_id' => 1, 'tag_id' => 36],
            ['article_id' => 1, 'tag_id' => 37],
            ['article_id' => 1, 'tag_id' => 38],
            ['article_id' => 1, 'tag_id' => 39],
            ['article_id' => 1, 'tag_id' => 40],
            ['article_id' => 1, 'tag_id' => 41],
            ['article_id' => 1, 'tag_id' => 42],
            ['article_id' => 1, 'tag_id' => 43],
            ['article_id' => 1, 'tag_id' => 44],
            ['article_id' => 1, 'tag_id' => 45],
            ['article_id' => 1, 'tag_id' => 46],
            ['article_id' => 1, 'tag_id' => 47],
            ['article_id' => 1, 'tag_id' => 48],
            ['article_id' => 1, 'tag_id' => 49],
            ['article_id' => 1, 'tag_id' => 50],
            ['article_id' => 1, 'tag_id' => 51],
            ['article_id' => 1, 'tag_id' => 52],
            ['article_id' => 1, 'tag_id' => 53],
            ['article_id' => 1, 'tag_id' => 54],
            ['article_id' => 1, 'tag_id' => 55],
            ['article_id' => 1, 'tag_id' => 56],
            ['article_id' => 1, 'tag_id' => 57],
            ['article_id' => 1, 'tag_id' => 58],
            ['article_id' => 1, 'tag_id' => 59],
            ['article_id' => 1, 'tag_id' => 60],
            ['article_id' => 1, 'tag_id' => 61],
            ['article_id' => 1, 'tag_id' => 62],
            ['article_id' => 1, 'tag_id' => 63],
            ['article_id' => 1, 'tag_id' => 64],
            ['article_id' => 1, 'tag_id' => 65],
            ['article_id' => 1, 'tag_id' => 66],
            ['article_id' => 1, 'tag_id' => 67],
            ['article_id' => 1, 'tag_id' => 68],
            ['article_id' => 1, 'tag_id' => 69],
            ['article_id' => 1, 'tag_id' => 70],
            ['article_id' => 1, 'tag_id' => 71],
            ['article_id' => 1, 'tag_id' => 72],
            ['article_id' => 1, 'tag_id' => 73],
            ['article_id' => 1, 'tag_id' => 74],
            ['article_id' => 1, 'tag_id' => 75],
            ['article_id' => 1, 'tag_id' => 76],
            ['article_id' => 1, 'tag_id' => 77],
            ['article_id' => 1, 'tag_id' => 78],
            ['article_id' => 1, 'tag_id' => 79],
            ['article_id' => 1, 'tag_id' => 80],
            ['article_id' => 1, 'tag_id' => 81],
            ['article_id' => 1, 'tag_id' => 82],
            ['article_id' => 1, 'tag_id' => 83],
            ['article_id' => 1, 'tag_id' => 84],
            ['article_id' => 1, 'tag_id' => 85],
            ['article_id' => 1, 'tag_id' => 86],
            ['article_id' => 1, 'tag_id' => 87],
            ['article_id' => 1, 'tag_id' => 88],
            ['article_id' => 1, 'tag_id' => 89],
            ['article_id' => 1, 'tag_id' => 90],
            ['article_id' => 1, 'tag_id' => 91],
            ['article_id' => 1, 'tag_id' => 92],
            ['article_id' => 1, 'tag_id' => 93],
            ['article_id' => 1, 'tag_id' => 94],
            ['article_id' => 1, 'tag_id' => 95],
            ['article_id' => 1, 'tag_id' => 96],
            ['article_id' => 1, 'tag_id' => 97],
            ['article_id' => 1, 'tag_id' => 98],
            ['article_id' => 1, 'tag_id' => 99],
            ['article_id' => 1, 'tag_id' => 100],   
        ];

        $stmt = $this->pdo->prepare('INSERT INTO users (username, email) VALUES (:username, :email)');
        foreach ($users as $user) {
            $stmt->execute($user);
        }

        $stmt = $this->pdo->prepare('INSERT INTO phones (phone_number, user_id) VALUES (:phone_number, :user_id)');
        foreach ($phones as $phone) {
            $stmt->execute($phone);
        }

        $stmt = $this->pdo->prepare('INSERT INTO articles (title, content, user_id) VALUES (:title, :content, :user_id)');
        foreach ($articles as $article) {
            $stmt->execute($article);
        }

        $stmt = $this->pdo->prepare('INSERT INTO comments (content, user_id, article_id) VALUES (:content, :user_id, :article_id)');
        foreach ($comments as $comment) {
            $stmt->execute($comment);
        }

        $stmt = $this->pdo->prepare('INSERT INTO tags (name) VALUES (:name)');
        foreach ($tags as $tag) {
            $stmt->execute($tag);
        }

        $stmt = $this->pdo->prepare('INSERT INTO article_tag (article_id, tag_id) VALUES (:article_id, :tag_id)');
        foreach ($article_tags as $article_tag) {
            $stmt->execute($article_tag);
        }
    }

    public function testCount()
    {
        $this->assertEquals(3, User::count());
        $this->assertEquals(25, Article::count());
        $this->assertEquals(25, Comment::count());
    }

    public function testFind()
    {
        $user = User::find(1);
        $this->assertEquals('john_doe', $user->username);
    }

    public function testFindAll()
    {
        $users = User::findAll();
        $this->assertCount(3, $users);
    }

    public function testFindLast()
    {
        $user = User::findLast();
        $this->assertEquals('peter_jones', $user->username);
    }

    public function testSelect()
    {
        $users = User::select('username')->get();  
        $this->assertCount(3, $users);
    }

    public function testSelectWithWhere()
    {
        $users = User::select('username')->where('username', 'john_doe')->get();    
        $this->assertCount(1, $users);
    }

    public function testMagicMethod()
    {
        $user = User::findByUsername('john_doe');
        $this->assertEquals('john_doe', $user->username);
    }

    public function testHasMany()
    {
        $user = User::with(['articles'])->first();
        $this->assertCount(9, $user->articles);
    }

    public function testBelongsTo()
    {
        $article = Article::with(['user'])->first();
        $this->assertEquals('john_doe', $article->user->username);
    }

    public function testHasOne()
    {
        $user = User::with(['phone'])->first();
        $this->assertEquals('1234567890', $user->phone->phone_number);
    }

    public function testBelongsToMany()
    {
        $article = Article::with(['tags'])->first();
        $this->assertCount(100, $article->tags);
    }

    public function testScope()
    {
        $users = Article::user_id_one()->get();
        $this->assertCount(9, $users);
    }
}