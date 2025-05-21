<?php
declare(strict_types=1);

namespace Cocoon\Database\Console;

use Symfony\Component\Console\Application as SymfonyApplication;
use Cocoon\Database\Console\Command\Migration\MigrateCommand;
use Cocoon\Database\Console\Command\Migration\StatusCommand;
use Cocoon\Database\Console\Command\Migration\RollbackCommand;
use Cocoon\Database\Console\Command\Migration\ResetCommand;
use Cocoon\Database\Console\Command\Migration\FreshCommand;
use Cocoon\Database\Console\Command\Generator\MakeMigrationCommand;
use Cocoon\Database\Console\Command\Generator\MakeModelCommand;
use Cocoon\Database\Console\Command\Generator\MakeRepositoryCommand;

/**
 * Application de console pour Cocoon Database
 */
class Application extends SymfonyApplication
{
    /**
     * Constructeur
     */
    public function __construct()
    {
        // Initialiser l'application parente avec le nom et la version
        parent::__construct('Cocoon Database CLI', '1.0.0');
        
        // Enregistrer toutes les commandes disponibles
        $this->registerCommands();
    }
    
    /**
     * Enregistre toutes les commandes disponibles dans l'application
     *
     * @return void
     */
    private function registerCommands(): void
    {
        // Commandes de migration
        $this->add(new MigrateCommand());
        $this->add(new StatusCommand());
        $this->add(new RollbackCommand());
        $this->add(new ResetCommand());
        $this->add(new FreshCommand());
        
        // Commandes de génération
        $this->add(new MakeMigrationCommand());
        $this->add(new MakeModelCommand());
        $this->add(new MakeRepositoryCommand());
    }
} 