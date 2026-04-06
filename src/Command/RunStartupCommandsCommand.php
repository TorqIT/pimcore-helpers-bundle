<?php

namespace Torq\PimcoreHelpersBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(name: 'torq:run-startup-commands', description: 'Runs all commands marked as `startup`.')]
class RunStartupCommandsCommand extends Command
{
        public function __construct(#[AutowireIterator('torq.startup_command')] private iterable $commands)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::SUCCESS;
    }
}