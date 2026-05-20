<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'torq:delete-objects', description: 'Deletes all objects for a given data object class.')]
class DeleteObjectsCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('class', InputArgument::REQUIRED, 'The class name of the data objects to delete')
             ->addOption(
                 'ids',
                 null,
                 InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                 'Specific object IDs to delete',
             )
             ->addOption(
                 'batch-size',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'Number of objects to delete per transaction',
                 50000,
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $className = $input->getArgument('class');
        $explicitIds = $input->getOption('ids');
        $batchSize = (int)$input->getOption('batch-size');

        $processed = 0;
        foreach ($this->batches($className, $explicitIds, $batchSize) as $i => $batch) {
            $this->connection->executeStatement('CALL DELETE_DATA_OBJECTS(?, ?)', [$className, json_encode($batch)]);
            $processed += count($batch);
            $io->write(sprintf("\r  Batch %d: %d objects deleted...", $i + 1, $processed));
        }
        $io->newLine();

        if ($processed === 0) {
            $io->warning("No $className objects found.");
            return self::SUCCESS;
        }

        $io->success("Deleted $processed $className objects.");
        return self::SUCCESS;
    }

    private function batches(string $className, array $explicitIds, int $batchSize): iterable
    {
        if (!empty($explicitIds)) {
            yield from array_chunk(array_map('intval', $explicitIds), $batchSize);
            return;
        }

        $lastId = 0;
        do {
            $batch = $this->connection->fetchFirstColumn(
                'SELECT id FROM objects WHERE className = ? AND id > ? ORDER BY id LIMIT ?',
                [$className, $lastId, $batchSize],
                [ParameterType::STRING, ParameterType::INTEGER, ParameterType::INTEGER],
            );
            if (!empty($batch)) {
                yield array_map('intval', $batch);
                $lastId = (int)max($batch);
            }
        } while (count($batch) === $batchSize);
    }
}
