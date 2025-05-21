<?php
declare(strict_types=1);

namespace Cocoon\Database\Console\Command\Generator;

use Cocoon\Database\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Cocoon\Database\Orm;
use Exception;

/**
 * Commande pour générer un nouveau modèle
 */
class MakeModelCommand extends Command
{
    /**
     * Configuration de la commande
     */
    protected function configure(): void
    {
        $this
            ->setName('make:model')
            ->setDescription('Crée un nouveau modèle')
            ->setHelp('Cette commande génère un nouveau modèle pour interagir avec une table de la base de données.')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Nom du modèle (singulier, en PascalCase)'
            )
            ->addOption(
                'migration',
                'm',
                InputOption::VALUE_NONE,
                'Créer une migration pour ce modèle'
            )
            ->addOption(
                'repository',
                'r',
                InputOption::VALUE_NONE,
                'Créer un repository pour ce modèle'
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Chemin du dossier des modèles',
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
            $createMigration = (bool)$input->getOption('migration');
            $createRepository = (bool)$input->getOption('repository');
            
            // Afficher le titre
            $this->io->title('Création d\'un nouveau modèle');
            
            // Créer le modèle
            $modelPath = $this->createModel($name, $input);
            
            // Afficher le succès pour le modèle
            $this->io->success("Modèle créé avec succès : {$name}");
            $this->io->text("Chemin complet : <info>{$modelPath}</info>");
            
            // Créer la migration si demandé
            if ($createMigration) {
                $this->io->section('Création de la migration associée');
                
                $tableName = $this->pluralize(strtolower($name));
                $migrationName = 'create_' . $tableName . '_table';
                
                $command = $this->getApplication()?->find('make:migration');
                
                if ($command) {
                    $arguments = [
                        'command' => 'make:migration',
                        'name' => $migrationName,
                        '--create' => $tableName
                    ];
                    
                    $migrationInput = new ArrayInput($arguments);
                    $returnCode = $command->run($migrationInput, $output);
                    
                    if ($returnCode !== self::SUCCESS) {
                        $this->io->warning("La création de la migration a échoué.");
                    }
                } else {
                    $this->io->warning("Commande make:migration non trouvée.");
                }
            }
            
            // Créer le repository si demandé
            if ($createRepository) {
                $this->io->section('Création du repository associé');
                
                $repositoryName = $name . 'Repository';
                
                $command = $this->getApplication()?->find('make:repository');
                
                if ($command) {
                    $arguments = [
                        'command' => 'make:repository',
                        'name' => $repositoryName,
                        '--model' => $name
                    ];
                    
                    $repositoryInput = new ArrayInput($arguments);
                    $returnCode = $command->run($repositoryInput, $output);
                    
                    if ($returnCode !== self::SUCCESS) {
                        $this->io->warning("La création du repository a échoué.");
                    }
                } else {
                    $this->io->warning("Commande make:repository non trouvée.");
                }
            }
            
            return self::SUCCESS;
        } catch (Exception $e) {
            $this->io->error([
                'Erreur lors de la création du modèle :',
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
     * Crée un nouveau fichier de modèle
     *
     * @param string $name Nom du modèle
     * @param InputInterface $input
     * @return string Chemin complet du fichier
     * @throws Exception
     */
    protected function createModel(string $name, InputInterface $input): string
    {
        // Vérifier que le nom est en PascalCase
        if (!preg_match('/^[A-Z][a-zA-Z0-9]+$/', $name)) {
            throw new Exception('Le nom du modèle doit être en PascalCase (ex: User, Product, BlogPost)');
        }
        
        // Déterminer le chemin du dossier des modèles
        $modelsPath = $this->getModelsPath($input);
        
        // S'assurer que le répertoire existe
        $this->ensureDirectoryExists($modelsPath);
        
        // Déterminer le chemin complet du fichier
        $filePath = $modelsPath . '/' . $name . '.php';
        
        // Vérifier si le fichier existe déjà
        if (file_exists($filePath)) {
            throw new Exception("Le modèle existe déjà : {$filePath}");
        }
        
        // Déduire le nom de la table à partir du nom du modèle
        $tableName = $this->pluralize(strtolower($name));
        
        // Générer le contenu du fichier
        $content = $this->generateModelContent($name, $tableName);
        
        // Écrire le fichier
        if (file_put_contents($filePath, $content) === false) {
            throw new Exception("Impossible d'écrire le fichier de modèle : {$filePath}");
        }
        
        return $filePath;
    }
    
    /**
     * Obtient le chemin du dossier des modèles
     *
     * @param InputInterface $input
     * @return string
     */
    protected function getModelsPath(InputInterface $input): string
    {
        // Utiliser le chemin spécifié en option, s'il existe
        $customPath = $input->getOption('path');
        if ($customPath !== null) {
            return $customPath;
        }
        
        // Sinon, utiliser le chemin par défaut
        $basePath = Orm::getConfig('base.path') ?? getcwd();
        return $basePath . '/app/Models';
    }
    
    /**
     * Génère le contenu du fichier de modèle
     *
     * @param string $name Nom du modèle
     * @param string $tableName Nom de la table
     * @return string
     */
    protected function generateModelContent(string $name, string $tableName): string
    {
        $dateTime = date('Y-m-d H:i:s');
        
        return <<<PHP
<?php
declare(strict_types=1);

namespace App\Models;

use Cocoon\Database\Model;

/**
 * Modèle {$name}
 * 
 * Généré le : {$dateTime}
 */
class {$name} extends Model
{
    /**
     * Nom de la table associée au modèle
     *
     * @var string|null
     */
    protected static ?string \$table = '{$tableName}';
    
    /**
     * Clé primaire de la table
     *
     * @var string
     */
    protected \$primaryKey = 'id';
    
    /**
     * Active l'enregistrement automatique des timestamps
     *
     * @var bool
     */
    protected \$timestamps = true;
    
    /**
     * Enregistre les champs date pour renvoyer une instance de Carbon Datetime
     *
     * @var array
     */
    protected \$dates = [];
    
    /**
     * Définition des relations du modèle
     *
     * @return array
     */
    public function relations()
    {
        // Définissez ici vos relations
        // Exemple: \$this->hasMany('App\\Models\\Comment', 'user_id');
        return [];
    }
    
    /**
     * Définition des observers du modèle
     *
     * @return void
     */
    public function observe()
    {
        // Définissez ici vos observers
        // Exemple: \$this->beforeSave(function() { /* code */ });
    }
    
    /**
     * Définition des scopes du modèle
     * Les scopes permettent de définir des requêtes réutilisables
     *
     * @return array
     */
    public static function scopes()
    {
        return [
            // Exemple de scope:
            // 'active' => function (\$query) {
            //     return \$query->where('status', 'active');
            // }
        ];
    }
}
PHP;
    }
    
    /**
     * Convertit un nom singulier en pluriel (simpliste)
     *
     * @param string $word
     * @return string
     */
    protected function pluralize(string $word): string
    {
        $lastChar = strtolower(substr($word, -1));
        
        // Règles de pluralisation très basiques
        if ($lastChar === 'y') {
            return substr($word, 0, -1) . 'ies';
        } elseif (in_array($lastChar, ['s', 'x', 'z'])) {
            return $word . 'es';
        } else {
            return $word . 's';
        }
    }
} 