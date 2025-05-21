<?php
declare(strict_types=1);

namespace Cocoon\Database\Console\Command\Generator;

use Cocoon\Database\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Cocoon\Database\Orm;
use Exception;

/**
 * Commande pour générer un nouveau repository
 */
class MakeRepositoryCommand extends Command
{
    /**
     * Configuration de la commande
     */
    protected function configure(): void
    {
        $this
            ->setName('make:repository')
            ->setDescription('Crée un nouveau repository')
            ->setHelp('Cette commande génère un nouveau repository pour encapsuler la logique d\'accès aux données.')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Nom du repository (en PascalCase, se terminant généralement par "Repository")'
            )
            ->addOption(
                'model',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Nom du modèle associé à ce repository',
                null
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Chemin du dossier des repositories',
                null
            );
    }
    
    /**
     * Exécution de la commande
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Récupérer les arguments et options
            $name = $input->getArgument('name');
            $modelName = $input->getOption('model');
            
            // Si le nom ne se termine pas par "Repository", l'ajouter
            if (!str_ends_with($name, 'Repository')) {
                $name .= 'Repository';
            }
            
            // Afficher le titre
            $this->io->title('Création d\'un nouveau repository');
            
            // Créer le repository
            $repositoryPath = $this->createRepository($name, $modelName, $input);
            
            // Afficher le succès
            $this->io->success("Repository créé avec succès : {$name}");
            $this->io->text("Chemin complet : <info>{$repositoryPath}</info>");
            
            return self::SUCCESS;
        } catch (Exception $e) {
            $this->io->error([
                'Erreur lors de la création du repository :',
                $e->getMessage()
            ]);
            
            if ($output->isVerbose()) {
                $this->io->section('Trace de l\'erreur :');
                $this->io->text($e->getTraceAsString());
            }
            
            return self::FAILURE;
        }
    }
    
    /**
     * Crée un nouveau fichier de repository
     *
     * @param string $name Nom du repository
     * @param string|null $modelName Nom du modèle associé
     * @param InputInterface $input
     * @return string Chemin complet du fichier
     * @throws Exception
     */
    protected function createRepository(string $name, ?string $modelName, InputInterface $input): string
    {
        // Vérifier que le nom est en PascalCase
        if (!preg_match('/^[A-Z][a-zA-Z0-9]+$/', $name)) {
            throw new Exception('Le nom du repository doit être en PascalCase (ex: UserRepository, ProductRepository)');
        }
        
        // Déterminer le chemin du dossier des repositories
        $repositoriesPath = $this->getRepositoriesPath($input);
        
        // S'assurer que le répertoire existe
        $this->ensureDirectoryExists($repositoriesPath);
        
        // Déterminer le chemin complet du fichier
        $filePath = $repositoriesPath . '/' . $name . '.php';
        
        // Vérifier si le fichier existe déjà
        if (file_exists($filePath)) {
            throw new Exception("Le repository existe déjà : {$filePath}");
        }
        
        // Générer le contenu du fichier
        $content = $this->generateRepositoryContent($name, $modelName);
        
        // Écrire le fichier
        if (file_put_contents($filePath, $content) === false) {
            throw new Exception("Impossible d'écrire le fichier de repository : {$filePath}");
        }
        
        return $filePath;
    }
    
    /**
     * Obtient le chemin du dossier des repositories
     *
     * @param InputInterface $input
     * @return string
     */
    protected function getRepositoriesPath(InputInterface $input): string
    {
        // Utiliser le chemin spécifié en option, s'il existe
        $customPath = $input->getOption('path');
        if ($customPath !== null) {
            return $customPath;
        }
        
        // Sinon, utiliser le chemin par défaut
        $basePath = Orm::getConfig('base.path') ?? getcwd();
        return $basePath . '/app/Repositories';
    }
    
    /**
     * Génère le contenu du fichier de repository
     *
     * @param string $name Nom du repository
     * @param string|null $modelName Nom du modèle associé
     * @return string
     */
    protected function generateRepositoryContent(string $name, ?string $modelName): string
    {
        $dateTime = date('Y-m-d H:i:s');
        
        // Déterminer le nom du modèle si non spécifié
        if ($modelName === null) {
            // Tenter de déduire le nom du modèle à partir du nom du repository
            if (str_ends_with($name, 'Repository')) {
                $modelName = substr($name, 0, -strlen('Repository'));
            } else {
                $modelName = 'Model'; // Fallback générique
            }
        }
        
        return <<<PHP
<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\\{$modelName};
use Cocoon\Database\Contracts\RepositoryInterface;

/**
 * Repository {$name}
 * 
 * Généré le : {$dateTime}
 */
class {$name} implements RepositoryInterface
{
    /**
     * Instance du modèle
     *
     * @var string
     */
    protected string \$modelClass;
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        \$this->modelClass = {$modelName}::class;
    }
    
    /**
     * Récupère tous les enregistrements
     *
     * @param string \$columns Colonnes à sélectionner
     * @param int|null \$paginate Nombre d'éléments par page
     * @param string \$orderByField Champ de tri
     * @param string \$order Direction du tri (asc|desc)
     * @return array<mixed>|object
     */
    public function all(
        string \$columns = '*',
        ?int \$paginate = null,
        string \$orderByField = 'id',
        string \$order = 'desc'
    ): array|object
    {
        \$query = \$this->modelClass::select(\$columns)
            ->orderBy(\$orderByField, \$order);
        
        if (\$paginate !== null) {
            return \$query->paginate(\$paginate);
        }
        
        return \$query->get();
    }

    /**
     * Recherche un enregistrement par son ID
     *
     * @param int|string \$id Identifiant de l'enregistrement
     * @param string \$columns Colonnes à sélectionner
     * @return array<mixed>|object|null
     */
    public function find(int|string \$id, string \$columns = '*'): array|object|null
    {
        return \$this->modelClass::find(\$id, \$columns);
    }

    /**
     * Crée un nouvel enregistrement
     *
     * @param array<string,mixed> \$data Données à enregistrer
     * @return array<mixed>|object Le modèle créé
     */
    public function save(array \$data = []): array|object
    {
        return \$this->modelClass::create(\$data);
    }

    /**
     * Met à jour un enregistrement existant
     *
     * @param int|string \$id Identifiant de l'enregistrement
     * @param array<string,mixed> \$data Données à mettre à jour
     * @return array<mixed>|object
     */
    public function update(int|string \$id, array \$data = []): array|object
    {
        \$this->modelClass::create(\$data, \$id);
        return \$this->find(\$id);
    }

    /**
     * Supprime un enregistrement
     *
     * @param int|string \$id Identifiant de l'enregistrement
     * @return void
     */
    public function delete(int|string \$id): void
    {
       (new \$this->modelClass(\$id))->delete();
    }

    /**
     * Recherche un enregistrement par un champ spécifique
     *
     * @param string \$field Champ de recherche
     * @param mixed \$value Valeur recherchée
     * @param string \$columns Colonnes à sélectionner
     * @return array<mixed>|object|null
     */
    public function findBy(string \$field, mixed \$value, string \$columns = '*'): array|object|null
    {
        return \$this->modelClass::select(\$columns)
            ->where(\$field, \$value)
            ->first();
    }

    /**
     * Recherche plusieurs enregistrements par un champ spécifique
     *
     * @param string \$field Champ de recherche
     * @param mixed \$value Valeur recherchée
     * @param string \$columns Colonnes à sélectionner
     * @return array<mixed>|object
     */
    public function findAllBy(string \$field, mixed \$value, string \$columns = '*'): array|object
    {
        return \$this->modelClass::select(\$columns)
            ->where(\$field, \$value)
            ->get();
    }

    /**
     * Compte le nombre total d'enregistrements
     *
     * @return int
     */
    public function count(): int
    {
        return \$this->modelClass::count();
    }

    /**
     * Permet d'accéder aux méthodes du Builder
     */
    public function __call(\$name, \$arguments)
    {
        return \$this->modelClass::{\$name}(...\$arguments);
    }
}
PHP;
    }
    
    /**
     * Convertit une chaîne en camelCase
     *
     * @param string $string
     * @return string
     */
    protected function camelCase(string $string): string
    {
        return lcfirst($string);
    }
}
