<?php
use Cocoon\Database\DB;
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
                'sqlite' => [
                    'path' => ':memory:',
                    'mode' => 'testing',
                    'db_cache_path' =>''
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
        
        // ...
        
        $users = [
            ['username' => 'john_doe', 'email' => 'john.doe@example.com'],
            ['username' => 'jane_smith', 'email' => 'jane.smith@example.com'],
            ['username' => 'peter_jones', 'email' => 'peter.jones@example.com'],
            // (3 auteurs)
        ];
        
        $articles = [
            ['title' => 'Article 1', 'content' => 'Contenu de l\'article 1', 'user_id' => 1],
            ['title' => 'Article 2', 'content' => 'Contenu de l\'article 2', 'user_id' => 2],
            ['title' => 'Article 3', 'content' => 'Contenu de l\'article 3', 'user_id' => 1],
            ['title'=> 'Article 4', 'content' => 'Contenu de l\'article 4', 'user_id' => 3],
            ['title'=> 'Article 5', 'content' => 'Contenu de l\'article 5', 'user_id' => 2],
            ['title'=> 'Article 6', 'content' => 'Contenu de l\'article 6', 'user_id' => 3],
            ['title'=> 'Article 7', 'content' => 'Contenu de l\'article 7', 'user_id' => 1],
            ['title'=> 'Article 8', 'content' => 'Contenu de l\'article 8', 'user_id' => 2],
            ['title'=> 'Article 9', 'content' => 'Contenu de l\'article 9', 'user_id' => 3],
            ['title'=> 'Article 10', 'content' => 'Contenu de l\'article 10', 'user_id' => 1],
            ['title'=> 'Article 11', 'content' => 'Contenu de l\'article 11', 'user_id' => 2],
            ['title'=> 'Article 12', 'content' => 'Contenu de l\'article 12', 'user_id' => 3],
            ['title'=> 'Article 13', 'content' => 'Contenu de l\'article 13', 'user_id' => 1],
            ['title'=> 'Article 14', 'content' => 'Contenu de l\'article 14', 'user_id' => 2],
            ['title'=> 'Article 15', 'content' => 'Contenu de l\'article 15', 'user_id' => 3],
            ['title'=> 'Article 16', 'content' => 'Contenu de l\'article 16', 'user_id' => 1],
            ['title'=> 'Article 17', 'content' => 'Contenu de l\'article 17', 'user_id' => 2],
            ['title'=> 'Article 18', 'content' => 'Contenu de l\'article 18', 'user_id' => 3],
            ['title'=> 'Article 19', 'content' => 'Contenu de l\'article 19', 'user_id' => 1],
            ['title'=> 'Article 20', 'content' => 'Contenu de l\'article 20', 'user_id' => 2],
            ['title'=> 'Article 21', 'content' => 'Contenu de l\'article 21', 'user_id' => 3],
            ['title'=> 'Article 22', 'content' => 'Contenu de l\'article 22', 'user_id' => 1],
            ['title'=> 'Article 23', 'content' => 'Contenu de l\'article 23', 'user_id' => 2],
            ['title'=> 'Article 24', 'content' => 'Contenu de l\'article 24', 'user_id' => 3],
            ['title'=> 'Article 25', 'content' => 'Contenu de l\'article 25', 'user_id' => 1],
            // ... (25 articles) 9 user 1, 8 user 2, 8 user 3

        ];
        
        $comments = [
            ['content' => 'Commentaire 1', 'user_id' => 2, 'article_id' => 1],
            ['content' => 'Commentaire 2', 'user_id' => 1, 'article_id' => 2],
            ['content' => 'Commentaire 3', 'user_id' => 3, 'article_id' => 1],
            ['content' => 'Commentaire 4', 'user_id' => 1, 'article_id' => 3],
            ['content'=> 'Commentaire 5', 'user_id' => 2, 'article_id' => 2],
            ['content'=> 'Commentaire 6', 'user_id' => 3, 'article_id' => 2],
            ['content'=> 'Commentaire 7', 'user_id' => 1, 'article_id' => 3],
            ['content'=> 'Commentaire 8', 'user_id' => 2, 'article_id' => 4],
            ['content'=> 'Commentaire 9', 'user_id' => 3, 'article_id' => 4],
            ['content'=> 'Commentaire 10', 'user_id' => 1, 'article_id' => 5],
            ['content'=> 'Commentaire 11', 'user_id' => 2, 'article_id' => 5],
            ['content'=> 'Commentaire 12', 'user_id' => 3, 'article_id' => 5],
            ['content'=> 'Commentaire 13', 'user_id' => 1, 'article_id' => 6],
            ['content'=> 'Commentaire 14', 'user_id' => 2, 'article_id' => 6],
            ['content'=> 'Commentaire 15', 'user_id' => 3, 'article_id' => 6],
            ['content'=> 'Commentaire 16', 'user_id' => 1, 'article_id' => 7],
            ['content'=> 'Commentaire 17', 'user_id' => 2, 'article_id' => 7],
            ['content'=> 'Commentaire 18', 'user_id' => 3, 'article_id' => 7],
            ['content'=> 'Commentaire 19', 'user_id' => 1, 'article_id' => 8],
            ['content'=> 'Commentaire 20', 'user_id' => 2, 'article_id' => 8],
            ['content'=> 'Commentaire 21', 'user_id' => 3, 'article_id' => 8],
            ['content'=> 'Commentaire 22', 'user_id' => 1, 'article_id' => 9],
            ['content'=> 'Commentaire 23', 'user_id' => 2, 'article_id' => 9],
            ['content'=> 'Commentaire 24', 'user_id' => 3, 'article_id' => 9],
            ['content'=> 'Commentaire 25', 'user_id' => 1, 'article_id' => 10],
            // ... (25 commentaires) 10 article 1, 5 article 2, 5 article 3, 5 article 4,
            //  5 article 5, 5 article 6, 5 article 7, 5 article 8, 5 article 9, 5 article 10
        ];
        
        $stmt = $this->pdo->prepare('INSERT INTO users (username, email) VALUES (:username, :email)');
        foreach ($users as $user) {
            $stmt->execute($user);
        }
        
        $stmt = $this->pdo->prepare('INSERT INTO articles (title, content, user_id) VALUES (:title, :content, :user_id)');
        foreach ($articles as $article) {
            $stmt->execute($article);
        }
        
        $stmt = $this->pdo->prepare('INSERT INTO comments (content, user_id, article_id) VALUES (:content, :user_id, :article_id)');
        foreach ($comments as $comment) {
            $stmt->execute($comment);
        }
        
        // ...
        

    }

    public function testConnection()
    {
        $this->assertIsObject(DI::get('db.connection'));
        $this->assertInstanceOf(PDO::class, DI::get('db.connection'));
    }

    public function testQueryBuilder()
    {
        $query = DB::table('articles')->get();
        $this->assertIsArray($query);
        $this->assertCount(25, $query);  
    }

}

