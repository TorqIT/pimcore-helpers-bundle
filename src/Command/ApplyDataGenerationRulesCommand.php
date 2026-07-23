<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Command;

use Pimcore\Model\DataObject\Concrete;
use Torq\PimcoreHelpersBundle\Service\DataGeneration\DataGenerationEngine;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Bulk-applies the config-driven DataGenerationRules to existing objects of the
 * managed classes. The same logic runs on every save via
 * DataGenerationListener; this command backfills the existing catalogue.
 */
#[AsCommand(
    name: 'torq:data-generation:apply',
    description: 'Apply DataGenerationRules to existing objects of the managed classes.'
)]
final class ApplyDataGenerationRulesCommand extends Command
{
    private const BATCH_SIZE = 200;

    public function __construct(
        private readonly DataGenerationEngine $engine,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('class', 'c', InputOption::VALUE_REQUIRED, 'Limit to a single managed class name')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Report what would change without saving')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Process at most N objects per class (0 = no limit)', '0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = (bool) $input->getOption('dry-run');
        $limit = max(0, (int) $input->getOption('limit'));
        $onlyClass = $input->getOption('class');

        $classes = $onlyClass !== null ? [$onlyClass] : $this->engine->getManagedClasses();

        if ($classes === []) {
            $io->warning('No managed classes are configured (torq_pimcore_helpers.data_generation.managed).');

            return Command::SUCCESS;
        }

        $io->title('Apply data generation rules');
        if ($isDryRun) {
            $io->note('Dry run: no objects will be saved.');
        }

        $totalChanged = 0;
        $totalScanned = 0;

        foreach ($classes as $className) {
            $listingClass = 'Pimcore\\Model\\DataObject\\' . $className . '\\Listing';
            if (!class_exists($listingClass)) {
                $io->warning(sprintf('No listing for class "%s" - skipping.', $className));
                continue;
            }

            $io->section($className);
            $scanned = 0;
            $changed = 0;
            $offset = 0;

            do {
                /** @var \Pimcore\Model\Listing\AbstractListing $listing */
                $listing = new $listingClass();
                $listing->setUnpublished(true);
                $listing->setOrderKey('id');
                $listing->setOrder('ASC');
                $listing->setOffset($offset);
                $listing->setLimit($limit > 0 ? min(self::BATCH_SIZE, $limit - $scanned) : self::BATCH_SIZE);

                $objects = $listing->load();
                foreach ($objects as $object) {
                    if (!$object instanceof Concrete) {
                        continue;
                    }
                    $scanned++;
                    $changedFields = $this->engine->apply($object);
                    if ($changedFields === []) {
                        continue;
                    }
                    $changed++;
                    if (!$isDryRun) {
                        $object->save(['versionNote' => 'torq:data-generation:apply']);
                    }
                }

                $offset += count($objects);
                \Pimcore::collectGarbage();
            } while ($objects !== [] && ($limit === 0 || $scanned < $limit));

            $io->text(sprintf('%s: %d changed of %d scanned', $className, $changed, $scanned));
            $totalChanged += $changed;
            $totalScanned += $scanned;
        }

        $io->success(sprintf(
            '%s %d of %d object(s).',
            $isDryRun ? 'Would update' : 'Updated',
            $totalChanged,
            $totalScanned,
        ));

        return Command::SUCCESS;
    }
}
