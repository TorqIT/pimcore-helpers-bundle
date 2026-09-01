<?php declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/generic-data-index')]
#[IsGranted('generic_data_index_tool')]
class GenericDataIndexController
{
    public function __construct(
        private KernelInterface $kernel,
    ) {
    }

    #[Route('/native-reindex', name: 'generic_data_index_native_reindex', methods: ['POST'])]
    public function nativeReindexAction(): Response
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $application->run(new ArrayInput(['command' => 'generic-data-index:reindex']), new NullOutput());

        return new Response(status: 204);
    }

    #[Route('/reindex', name: 'generic_data_index_reindex', methods: ['POST'])]
    public function reindexAction(): Response
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $application->run(new ArrayInput(['command' => 'generic-data-index:update:index']), new NullOutput());

        return new Response(status: 204);
    }

    #[Route('/recreate', name: 'generic_data_index_recreate', methods: ['POST'])]
    public function recreateIndexAction(): Response
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $application->run(new ArrayInput(['command' => 'generic-data-index:update:index', '-r' => true]), new NullOutput());

        return new Response(status: 204);
    }
}
