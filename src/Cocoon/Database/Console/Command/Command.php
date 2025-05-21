<?php
declare(strict_types=1);

namespace Cocoon\Database\Console\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Cocoon\Database\Orm;
use Exception;

/**
 * Classe de base pour toutes les commandes Cocoon
 */
abstract class Command extends SymfonyCommand
{
    /**
     * Instance de SymfonyStyle pour formater la sortie
     *
     * @var SymfonyStyle
     */
    protected $io;
    
    /**
     * Initialise la commande avant l'exécution
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // Créer l'objet SymfonyStyle pour une sortie formatée
        $this->io = new SymfonyStyle($input, $output);
        
        // Initialiser la connexion à la base de données
        $this->initializeDatabase();
    }
    
    /**
     * Initialise la connexion à la base de données
     *
     * @return void
     */
    protected function initializeDatabase(): void
    {
        try {
            // Chemin du fichier de configuration
            $configFile = $this->getConfigPath();
            
            // Vérifier si le fichier existe
            if (!file_exists($configFile)) {
                throw new Exception("Le fichier de configuration n'existe pas : {$configFile}");
            }
            
            // Charger la configuration
            $config = require $configFile;
            
            // Vérifier la structure de la configuration
            if (!isset($config['db_driver']) || !isset($config['db'][$config['db_driver']])) {
                throw new Exception("Format de configuration invalide dans {$configFile}");
            }
            
            // Initialiser le gestionnaire de base de données
            Orm::manager($config['db_driver'], $config['db'][$config['db_driver']]);
        } catch (Exception $e) {
            $this->io?->error("Erreur d'initialisation de la base de données : " . $e->getMessage());
            exit(1);
        }
    }
    
    /**
     * Obtient le chemin du fichier de configuration
     *
     * @return string
     */
    protected function getConfigPath(): string
    {
        // Par défaut, on cherche le fichier de configuration dans le répertoire config
        $basePath = getcwd();
        
        // Liste des chemins possibles pour le fichier de configuration
        $possiblePaths = [
            $basePath . '/config/database.php'
        ];
        
        // Retourner le premier fichier qui existe
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Si aucun fichier n'est trouvé, utiliser une configuration par défaut
        return $basePath . '/config/database.php';
    }
    
    /**
     * Vérifie si un répertoire existe et le crée s'il n'existe pas
     *
     * @param string $directory Chemin du répertoire
     * @return void
     * @throws Exception Si le répertoire ne peut pas être créé
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new Exception("Impossible de créer le répertoire : {$directory}");
            }
        }
    }
}
