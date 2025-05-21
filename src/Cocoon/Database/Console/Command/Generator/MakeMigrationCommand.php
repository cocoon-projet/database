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
 * Commande pour générer un fichier de migration
 */
class MakeMigrationCommand extends Command
{
    /**
     * Configuration de la commande
     */
    protected function configure(): void
    {
        $this
            ->setName('make:migration')
            ->setDescription('Crée un nouveau fichier de migration')
            ->setHelp('Cette commande génère un nouveau fichier de migration dans le dossier spécifié.')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Nom de la migration (sans l\'extension .php)'
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Chemin du dossier des migrations',
                null
            )
            ->addOption(
                'create',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Nom de la table à créer',
                null
            )
            ->addOption(
                'table',
                't',
                InputOption::VALUE_OPTIONAL,
                'Nom de la table à modifier',
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
            $createTable = $input->getOption('create');
            $updateTable = $input->getOption('table');
            
            // Obtenir le chemin des migrations
            $migrationsPath = $this->getMigrationsPath($input);
            
            // Afficher le titre
            $this->io->title('Création d\'une nouvelle migration');
            
            // S'assurer que le répertoire des migrations existe
            $this->ensureDirectoryExists($migrationsPath);
            
            // Générer le nom de fichier avec timestamp
            $fileName = $this->generateFileName($name);
            $filePath = $migrationsPath . '/' . $fileName . '.php';
            
            // Vérifier si le fichier existe déjà
            if (file_exists($filePath)) {
                $this->io->error("Le fichier de migration existe déjà : {$filePath}");
                return self::FAILURE;
            }
            
            // Déterminer le type de migration
            $type = $this->determineMigrationType($createTable, $updateTable);
            
            // Générer le contenu du fichier
            $content = $this->generateMigrationContent($fileName, $type, $createTable ?? $updateTable);
            
            // Écrire le fichier
            if (file_put_contents($filePath, $content) === false) {
                throw new Exception("Impossible d'écrire le fichier de migration : {$filePath}");
            }
            
            // Afficher le succès
            $this->io->success("Migration créée avec succès : {$fileName}.php");
            $this->io->text("Chemin complet : <info>{$filePath}</info>");
            
            return self::SUCCESS;
        } catch (Exception $e) {
            $this->io->error([
                'Erreur lors de la création de la migration :',
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
     * Obtient le chemin des migrations
     *
     * @param InputInterface $input
     * @return string
     */
    protected function getMigrationsPath(InputInterface $input): string
    {
        // Utiliser le chemin spécifié en option, s'il existe
        $customPath = $input->getOption('path');
        if ($customPath !== null) {
            return $customPath;
        }
        
        // Sinon, utiliser le chemin par défaut
        $basePath = Orm::getConfig('base.path') ?? getcwd();
        return $basePath . '/database/migrations';
    }
    
    /**
     * Génère un nom de fichier unique avec timestamp
     *
     * @param string $name Nom de base de la migration
     * @return string
     */
    protected function generateFileName(string $name): string
    {
        // Formater le nom pour qu'il soit en snake_case
        $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        $name = str_replace(' ', '_', $name);
        
        // Ajouter un timestamp comme préfixe au format YYYY_MM_DD_HHMMSS
        // Ce format est important car le Migrator l'utilisera pour déterminer l'ordre d'exécution
        // et extraire le nom de la classe
        $timestamp = date('Y_m_d_His');
        
        return $timestamp . '_' . $name;
    }
    
    /**
     * Détermine le type de migration à générer
     *
     * @param string|null $createTable
     * @param string|null $updateTable
     * @return string
     */
    protected function determineMigrationType(?string $createTable, ?string $updateTable): string
    {
        if ($createTable !== null) {
            return 'create';
        } elseif ($updateTable !== null) {
            return 'update';
        } else {
            return 'generic';
        }
    }
    
    /**
     * Génère le contenu du fichier de migration
     *
     * @param string $fileName Nom du fichier sans extension
     * @param string $type Type de migration (create, update, generic)
     * @param string|null $tableName Nom de la table concernée
     * @return string
     */
    protected function generateMigrationContent(string $fileName, string $type, ?string $tableName): string
    {
        // Extraire le nom sans le timestamp (la partie après le timestamp)
        // Le format du fileName est: YYYY_MM_DD_HHMMSS_nom_de_la_migration
        $nameParts = explode('_', $fileName);
        
        // On extrait la partie du nom sans timestamp (après les 4 premiers segments)
        // Ces 4 segments correspondent au format de date: année_mois_jour_heureminuteseconde
        $nameWithoutTimestamp = implode('_', array_slice($nameParts, 4));
        
        // Convertir le nom en PascalCase pour la classe
        // IMPORTANT: Le nom de la classe ne doit pas contenir le timestamp,
        // sinon il y aura un décalage avec la fonction getClassName du Migrator
        $className = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $nameWithoutTimestamp)));
        
        // Si le nom de classe est vide, utiliser un nom générique
        if (empty($className)) {
            $className = 'Migration' . date('YmdHis');
        }
        
        // Récupérer la date/heure de création
        $dateTime = date('Y-m-d H:i:s');
        
        // Contenu différent selon le type de migration
        if ($type === 'create') {
            return $this->generateCreateTableMigration($className, $tableName, $dateTime);
        } elseif ($type === 'update') {
            return $this->generateUpdateTableMigration($className, $tableName, $dateTime);
        } else {
            return $this->generateGenericMigration($className, $dateTime);
        }
    }
    
    /**
     * Génère une migration de création de table
     *
     * @param string $className
     * @param string $tableName
     * @param string $dateTime
     * @return string
     */
    protected function generateCreateTableMigration(string $className, string $tableName, string $dateTime): string
    {
        return <<<PHP
<?php
declare(strict_types=1);

use Cocoon\Database\Migrations\Migration;
use Cocoon\Database\Migrations\Built;

/**
 * Migration pour créer la table '{$tableName}'
 * 
 * Générée le : {$dateTime}
 */
class {$className} extends Migration
{
    /**
     * Nom de la table
     *
     * @var string
     */
    protected \$tableName = '{$tableName}';
    
    /**
     * Exécute la migration
     *
     * @return void
     */
    public function up()
    {
        Built::schema()->create(\$this->tableName, function (\$table) {
            \$table->id();
            // Ajoutez vos colonnes ici
            
            \$table->timestamps();
        });
    }
    
    /**
     * Annule la migration
     *
     * @return void
     */
    public function down()
    {
        Built::schema()->drop(\$this->tableName);
    }
}
PHP;
    }
    
    /**
     * Génère une migration de modification de table
     *
     * @param string $className
     * @param string $tableName
     * @param string $dateTime
     * @return string
     */
    protected function generateUpdateTableMigration(string $className, string $tableName, string $dateTime): string
    {
        return <<<PHP
<?php
declare(strict_types=1);

use Cocoon\Database\Migrations\Migration;
use Cocoon\Database\Migrations\Built;

/**
 * Migration pour modifier la table '{$tableName}'
 * 
 * Générée le : {$dateTime}
 */
class {$className} extends Migration
{
    /**
     * Nom de la table
     *
     * @var string
     */
    protected \$tableName = '{$tableName}';
    
    /**
     * Exécute la migration
     *
     * @return void
     */
    public function up()
    {
        Built::schema()->table(\$this->tableName, function (\$table) {
            // Ajoutez vos modifications ici
            // Exemple : \$table->string('email')->nullable();
        });
    }
    
    /**
     * Annule la migration
     *
     * @return void
     */
    public function down()
    {
        Built::schema()->table(\$this->tableName, function (\$table) {
            // Annulez vos modifications ici
            // Exemple : \$table->dropColumn('email');
        });
    }
}
PHP;
    }
    
    /**
     * Génère une migration générique
     *
     * @param string $className
     * @param string $dateTime
     * @return string
     */
    protected function generateGenericMigration(string $className, string $dateTime): string
    {
        return <<<PHP
<?php
declare(strict_types=1);

use Cocoon\Database\Migrations\Migration;
use Cocoon\Database\Migrations\Built;

/**
 * Migration générique
 * 
 * Générée le : {$dateTime}
 */
class {$className} extends Migration
{
    /**
     * Exécute la migration
     *
     * @return void
     */
    public function up()
    {
        // Implémentez les modifications à appliquer
    }
    
    /**
     * Annule la migration
     *
     * @return void
     */
    public function down()
    {
        // Implémentez les modifications pour annuler la migration
    }
}
PHP;
    }
}
