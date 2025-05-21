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
 * Commande pour exécuter les migrations en attente
 */
class MigrateCommand extends Command
{
    /**
     * Configuration de la commande
     */
    protected function configure(): void
    {
        $this
            ->setName('migrate')
            ->setDescription('Exécute les migrations en attente')
            ->setHelp(
                'Cette commande exécute toutes les migrations qui n\'ont pas encore été appliquées.'
                . PHP_EOL .
                'Utilisez l\'option --path pour spécifier un chemin différent pour les migrations.'
            )
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
            $this->io->title('Migration de la base de données');
            $this->io->text("Utilisation du chemin : <info>{$migrationsPath}</info>");

            // Exécuter les migrations
            $migrator = new Migrator($migrationsPath);

            // Afficher l'état des migrations avant l'exécution
            $statusBefore = $migrator->status();
            $pendingCount = count(array_filter($statusBefore, function ($info) {
                return !$info['applied'];
            }));

            if ($pendingCount === 0) {
                $this->io->success('Aucune migration en attente.');
                return self::SUCCESS;
            }

            $this->io->text("<fg=yellow>{$pendingCount} migration(s) en attente d'application.</>");

            // Exécuter les migrations
            $this->io->newLine();
            $this->io->section('Exécution des migrations');

            $migrator->run();

            // Afficher un récapitulatif
            $this->io->newLine();
            $this->io->success("Migrations appliquées avec succès !");

            return self::SUCCESS;
        } catch (MigrationException $e) {
            // Afficher une erreur formatée pour les exceptions de migration
            $this->io->error([
                'Erreur de migration : ' . $e->getMessage(),
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
                'Erreur lors de l\'exécution des migrations :',
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
