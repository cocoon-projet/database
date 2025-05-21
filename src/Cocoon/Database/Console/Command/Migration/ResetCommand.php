<?php
declare(strict_types=1);

namespace Cocoon\Database\Console\Command\Migration;

use Cocoon\Database\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Cocoon\Database\Orm;
use Cocoon\Database\Migrations\Migrator;
use Cocoon\Database\Exception\MigrationException;
use Exception;

/**
 * Commande pour annuler toutes les migrations
 */
class ResetCommand extends Command
{
    /**
     * Configuration de la commande
     */
    protected function configure(): void
    {
        $this
            ->setName('migrate:reset')
            ->setDescription('Annule toutes les migrations')
            ->setHelp('Cette commande annule toutes les migrations appliquées à la base de données.')
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Chemin du dossier des migrations',
                null
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Forcer l\'opération sans demander de confirmation'
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
            $this->io->title('Réinitialisation de la base de données');
            $this->io->text("Utilisation du chemin : <info>{$migrationsPath}</info>");
            $this->io->newLine();
            
            // Demander confirmation sauf si --force est utilisé
            if (!$input->getOption('force')) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    '<fg=red>ATTENTION : Toutes les tables de la base de données seront réinitialisées. 
                    Cette opération est irréversible.</>'
                    . PHP_EOL
                    . 'Voulez-vous continuer ? (y/n) ',
                    false
                );
                
                if (!$helper->ask($input, $output, $question)) {
                    $this->io->text('Opération annulée.');
                    return self::SUCCESS;
                }
            }
            
            // Créer le migrator
            $migrator = new Migrator($migrationsPath);
            
            // Afficher l'état des migrations avant l'exécution
            $statusBefore = $migrator->status();
            $appliedCount = count(array_filter($statusBefore, function ($info) {
                return $info['applied'];
            }));
            
            if ($appliedCount === 0) {
                $this->io->success('Aucune migration à réinitialiser.');
                return self::SUCCESS;
            }
            
            $this->io->text("<fg=yellow>{$appliedCount} migration(s) vont être annulées.</>");
            
            // Exécuter la réinitialisation
            $this->io->newLine();
            $this->io->section('Réinitialisation des migrations');
            
            $migrator->reset();
            
            // Afficher un récapitulatif
            $this->io->newLine();
            $this->io->success("Base de données réinitialisée avec succès !");
            
            return self::SUCCESS;
        } catch (MigrationException $e) {
            // Afficher une erreur formatée pour les exceptions de migration
            $this->io->error([
                'Erreur lors de la réinitialisation : ' . $e->getMessage(),
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
                'Erreur lors de la réinitialisation :',
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
