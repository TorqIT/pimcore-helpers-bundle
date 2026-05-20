<?php

namespace Torq\PimcoreHelpersBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'torq:delete-objects', description: 'Deletes all objects for a given data object class.')]
class DeleteObjectsCommand extends Command
{
    public function __construct(private Connection $connection)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('class', InputArgument::REQUIRED, "The class name of the data objects to fully delete");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::SUCCESS;
    }
}