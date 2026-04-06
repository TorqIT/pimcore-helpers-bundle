<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Command;

use Carbon\Carbon;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Torq\PimcoreHelpersBundle\Attribute\AsStartupCommand;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

#[AsCommand(name: 'torq:run-startup-commands', description: 'Runs all commands marked as `startup`.')]
class RunStartupCommandsCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private ArrayUtils $utils,
        #[AutowireIterator('torq.startup_command')] private readonly iterable $commands,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->commands as $command) {
            $commandName = $command->getName();
            if (!$this->isCommandValid($command, $output)) {
                continue;
            }

            if (!($this->isRepeatable($command))) {
                $alreadyRun = (int)$this->connection->fetchOne(
                        'SELECT COUNT(*) FROM startup_command_runs WHERE name = ?',
                        [$commandName]
                    ) > 0;

                if ($alreadyRun) {
                    $output->writeln(sprintf('<info>Skipping %s: already run.</info>', $commandName));
                    continue;
                }
            }

            $output->writeln(sprintf('<info>Running %s...</info>', $commandName));
            $exitCode = $command->run(new ArrayInput([]), $output);
            if ($exitCode !== self::SUCCESS) {
                $output->writeln(
                    sprintf('<error>Command %s failed with exit code %d.</error>', $commandName, $exitCode)
                );
                return self::FAILURE;
            }

            $today = Carbon::now()->format('Y-m-d');
            try {
                $this->connection->insert('startup_command_runs', [
                    'name' => $commandName,
                    'executed_at' => $today,
                ]);
            } catch (UniqueConstraintViolationException) {
                $this->connection->update(
                    'startup_command_runs',
                    ['executed_at' => $today], ['name' => $commandName]
                );
            }

            $output->writeln(sprintf('<info>Completed %s.</info>', $commandName));
        }

        return self::SUCCESS;
    }

    private function isCommandValid(mixed $command, OutputInterface $output)
    {
        if (!$command instanceof Command) {
            $output->writeln(
                sprintf(
                    '<comment>Skipping %s: does not extend Symfony Command.</comment>',
                    get_class($command)
                )
            );
            return false;
        } elseif (
            !empty(
            $requiredArguments = array_filter(
                $command->getDefinition()->getArguments(),
                fn($arg) => $arg->isRequired()
            )
            )
        ) {
            $output->writeln(
                sprintf(
                    '<comment>Skipping %s: has required arguments (%s) and cannot be run unattended.</comment>',
                    $command->getName(),
                    implode(', ', array_keys($requiredArguments))
                )
            );
            return false;
        } else {
            return true;
        }
    }

    private function isRepeatable(Command $command)
    {
        $reflection = new ReflectionClass($command);
        $attributes = $reflection->getAttributes(AsStartupCommand::class);
        /** @var AsStartupCommand $startupAttribute */
        $startupAttribute = $this->utils->get('0', $attributes)?->newInstance();
        return $this->utils->get(['0', 'torq.startup_command', 'repeatable'], $startupAttribute->tags, false);
    }
}