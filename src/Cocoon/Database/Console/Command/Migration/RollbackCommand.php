<?php
declare(strict_types=1);

namespace Cocoon\Database\Console\Command\Migration;

use Cocoon\Database\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Cocoon\Database\Orm;
use Cocoon\Database\Migrations\Migrator;
use Cocoon\Database\Exception\MigrationException;
use Exception;

/**
 * Commande pour annuler les dernières migrations
 */
class RollbackCommand extends Command
{
    /**
     * Configuration de la commande
     */
    protected function configure(): void
    {
        $this
            ->setName('migrate:rollback')
            ->setDescription('Annule les dernières migrations')
            ->setHelp('Cette commande annule le dernier lot de migrations appliquées.')
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Chemin du dossier des migrations',
                null
            )
            ->addOption(
                'step', 
                's',
                InputOption::VALUE_OPTIONAL,
                'Nombre de lots de migrations à annuler',
                '1'
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
            $this->io->title('Annulation des migrations');
            $this->io->text("Utilisation du chemin : <info>{$migrationsPath}</info>");
            
            // Créer le migrator
            $migrator = new Migrator($migrationsPath);
            
            // Afficher l'état des migrations avant l'annulation
            $statusBefore = $migrator->status();
            $appliedCount = count(array_filter($statusBefore, function($info) {
                return $info['applied'];
            }));
            
            if ($appliedCount === 0) {
                $this->io->success('Aucune migration à annuler.');
                return self::SUCCESS;
            }
            
            // Exécuter le rollback
            $this->io->newLine();
            $this->io->section('Annulation des migrations');
            
            // TODO: Implémenter le support des steps quand Migrator supportera cette fonctionnalité
            $migrator->rollback();
            
            // Afficher un récapitulatif
            $this->io->newLine();
            $this->io->success("Migrations annulées avec succès !");
            
            return self::SUCCESS;
        } catch (MigrationException $e) {
            // Afficher une erreur formatée pour les exceptions de migration
            $this->io->error([
                'Erreur lors de l\'annulation des migrations : ' . $e->getMessage(),
                'Code : ' . $e->getCode()
            ]);
            
            // Afficher le contexte s'il est disponible
            $context = $e->getContext();
            if (!empty($context)) {
                $this->io->section('Contexte de l\'erreur :');
                foreach ($context as $key => $value) {
                    $this->io->text("<info>{$key}:</info> {$value}");
                }
            }
            
            return self::FAILURE;
        } catch (Exception $e) {
            // Afficher une erreur générique pour les autres types d'exceptions
            $this->io->error([
                'Erreur lors de l\'annulation des migrations :',
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