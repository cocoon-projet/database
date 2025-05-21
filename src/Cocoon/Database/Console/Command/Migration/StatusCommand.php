<?php
declare(strict_types=1);

namespace Cocoon\Database\Console\Command\Migration;

use Cocoon\Database\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Cocoon\Database\Orm;
use Cocoon\Database\Migrations\Migrator;
use Exception;

/**
 * Commande pour afficher l'état des migrations
 */
class StatusCommand extends Command
{
    /**
     * Configuration de la commande
     */
    protected function configure(): void
    {
        $this
            ->setName('migrate:status')
            ->setDescription('Affiche l\'état des migrations')
            ->setHelp('Cette commande affiche l\'état de toutes les migrations (appliquées ou en attente).')
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Chemin du dossier des migrations',
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
            // Obtenir le chemin des migrations
            $migrationsPath = $this->getMigrationsPath($input);
            
            // Afficher le titre
            $this->io->title('État des migrations');
            $this->io->text("Utilisation du chemin : <info>{$migrationsPath}</info>");
            
            // Créer le migrator et récupérer le statut
            $migrator = new Migrator($migrationsPath);
            $status = $migrator->status();
            
            if (empty($status)) {
                $this->io->warning('Aucune migration trouvée dans le répertoire spécifié.');
                return self::SUCCESS;
            }
            
            // Préparer les données pour le tableau
            $rows = [];
            $appliedCount = 0;
            $pendingCount = 0;
            
            foreach ($status as $migration => $info) {
                $statusText = $info['applied'] 
                    ? '<fg=green>Appliquée</>' 
                    : '<fg=yellow>En attente</>';
                
                $batch = $info['batch'] ?? '-';
                
                $rows[] = [
                    $info['file'],
                    $statusText,
                    $batch
                ];
                
                if ($info['applied']) {
                    $appliedCount++;
                } else {
                    $pendingCount++;
                }
            }
            
            // Afficher le tableau
            $this->io->table(
                ['Migration', 'Statut', 'Batch'],
                $rows
            );
            
            // Afficher le récapitulatif
            $this->io->section('Récapitulatif');
            $this->io->text([
                "Total des migrations : <info>" . count($status) . "</info>",
                "Migrations appliquées : <info>{$appliedCount}</info>",
                "Migrations en attente : <info>{$pendingCount}</info>"
            ]);
            
            return self::SUCCESS;
        } catch (Exception $e) {
            $this->io->error([
                'Erreur lors de la récupération du statut des migrations :',
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
} 